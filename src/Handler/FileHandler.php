<?php

declare(strict_types=1);

namespace GallimimusFilesModule\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Diactoros\UploadedFile;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Platform;
use Zend\Db\Sql\Platform\AbstractPlatform;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Update;
use Zend\Diactoros\Response;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Stream;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

use function time;

class FileHandler implements RequestHandlerInterface
{
    private $renderer;
	private $parameters;
	private $config;
	private $adapter;
	private $router;

    public function __construct(
			$renderer, 
			$adapter, 
			$config,
			$router)
    {
        $this->renderer = $renderer;
		$this->adapter = $adapter;
        $this->config = $config;
        $this->router = $router;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
		$plik = array();
		$id = (int)$request->getAttribute('id');

		$opcjaWywolania = explode("/",$request->getServerParams()["REQUEST_URI"])[1];

		if($id > 0) {
			try {
				$sql = "select * from file where id_file = $id";
				$statement = $this->adapter->query($sql);
				$results = $statement->execute();
				foreach($results as $res)
				{
					$plik = $res;
				}
			} catch(Exception $e) { return $this->byk($e->getMessage()); }
		}

		if(count($plik) == 0) return $this->byk("Błędny indeks pliku");

		$pathToImages = $this->config["vizconfig"]["pathToImages"];
		$rozszerzenie = $plik["type"];
		$idZaktualizowanegoRekordu = $plik["id_file"];
		$contentType = $plik["content_type"];
		$zera = "0000000000";
		$zera = substr($zera,0,10-strlen($idZaktualizowanegoRekordu.''));
		$nazwaDocelowa = $zera . $idZaktualizowanegoRekordu;
		if($opcjaWywolania == "file" || $opcjaWywolania == "img" || $plik["type"] == "svg")
			$target_file = $pathToImages . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR .  $nazwaDocelowa . '.' . $rozszerzenie;

		$target_file = $_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR .$target_file;
		$target_file = str_replace("public",DIRECTORY_SEPARATOR,$target_file);
		$target_file = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$target_file);
		$target_file = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$target_file);

		if(($opcjaWywolania == "thmb" || $opcjaWywolania == "ico") && $plik["type"] != "svg")
			{
				$target_file_if = $pathToImages . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR .  $nazwaDocelowa . '.' . $rozszerzenie;
				if(file_exists($target_file_if)) $target_file = $target_file_if;
				else $target_file = $pathToImages . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR .  $nazwaDocelowa . '.' . $rozszerzenie;
			}

		$target_file = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$target_file);
		// return $this->byk($target_file);
		$imageContent =  file_get_contents($target_file);
		
		$headers = [
			'Content-Transfer-Encoding' => 'Binary',
			'Content-Description' => 'File Transfer',
			'Pragma' => 'public',
			'Expires' => '0',
			'Cache-Control' => 'must-revalidate',
			'Content-Type' => $contentType,
		];

		return new HtmlResponse(
			$imageContent,
			200,
			['Content-Type' => [$contentType]]
		);
 
		return $this->byk($plik);
    }

	public function byk($mess)
	{
		return new JsonResponse([
				'ack' => time(),
				'page' => 0,
				'limit' => 0,
				'results' => "",
				'count' => 0,
				'status' => "error",
				'message' => $mess,
				]);
	}		
}

