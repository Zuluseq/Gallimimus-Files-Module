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

class UploadHandler implements RequestHandlerInterface
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
		// AUTORYZACJA
		// $opcjaWywolania = explode("/",$request->getServerParams()["REQUEST_URI"])[1];
		// weryfikacja parametrów wejściowych

		// return $this->byk($_FILES["file"]);
		if(!isset($_FILES["file"])) return $this->byk("błąd przy przesyłaniu pliku");
		if(!isset($this->config["vizconfig"]["pathToImages"])) return $this->byk("błąd konfiguracji brakje pathToImages");



		$pathToImages = $this->config["vizconfig"]["pathToImages"];
		$fileName = $_FILES["file"]["name"];
		$pathToTmp = $_FILES["file"]["tmp_name"];
		$size = $_FILES["file"]["size"];
		$type = $_FILES["file"]["type"];
		$rozszerzenie = $this->pobierzRozszerzenie($type);

		if($_FILES["file"]["error"] > 0) return $this->byk("plik zbyt duży");
		if($rozszerzenie == "xxx") return $this->byk($pathToTmp);
		if($rozszerzenie == "xxx") return $this->byk("niedozwolony typ MIME pliku");
		if(!file_exists($pathToImages)) return $this->byk("brakuje katalogu na pliki: $pathToImages");
		if(!file_exists($pathToImages . DIRECTORY_SEPARATOR . 'originals' )) mkdir($pathToImages . DIRECTORY_SEPARATOR . 'originals' , 0700);
		if(!file_exists($pathToImages . DIRECTORY_SEPARATOR . 'thumbnails' )) mkdir($pathToImages . DIRECTORY_SEPARATOR . 'thumbnails' , 0700);

		//TODO pobrać usera

		$id_gallery = 0;
		$id_user = 0;
		$keywords = '';
		$description = '';
		$destination = '';
		$status = "aktywny";

		$query = $request->getQueryParams();
		if($query['idg']) $id_gallery = $query['idg'];

		$data = array(
			'id_gallery' => $id_gallery,
			'id_user' => $id_user,
			'name' => $fileName,
			'slug' => null,
			'type' => $rozszerzenie,
			'content_type' => $type,
			'size' => $size,
			'keywords' => $keywords,
			'description' => $description,
			'destination' => $destination,
			'status' => $status);
		
		$platforma = $this->adapter->getPlatform();
		$insert = new Insert($this->adapter);
		$insert->into("file");
		$insert->values($data);
		$sql = $insert->getSqlString($platforma);
		$results = $this->adapter->query($sql, $this->adapter::QUERY_MODE_EXECUTE);
		$idZaktualizowanegoRekordu = $this->adapter->getDriver()->getLastGeneratedValue();
		
		$zera = "0000000000";
		$zera = substr($zera,0,10-strlen($idZaktualizowanegoRekordu.''));
		$nazwaDocelowa = $zera . $idZaktualizowanegoRekordu;
		
		$rozszerzenie = \strtolower($rozszerzenie);
		
		$target_file = $pathToImages . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR .  $nazwaDocelowa . '.' . $rozszerzenie;
		$target_thumbnail = $pathToImages . DIRECTORY_SEPARATOR . 'thumbnails' . DIRECTORY_SEPARATOR .  $nazwaDocelowa . '.' . $rozszerzenie;
		
		if($rozszerzenie == 'jpg' || $rozszerzenie == 'jpeg' || $rozszerzenie == 'png' || $rozszerzenie == 'svg')
		{
			move_uploaded_file($pathToTmp, $target_file);
			if (extension_loaded('imagick'))
			{
				$imagick = new \Imagick(realpath($target_file));
				$imagick->setImageFormat('jpeg');
				$imagick->setImageCompressionQuality(90);
				$imagick->thumbnailImage(400, 400, false, false);
				file_put_contents($target_thumbnail, $imagick);
			} else {
				copy($target_file, $target_thumbnail);
			}
		}
		
        return new JsonResponse([
			'ack' => time(),
			'page' => 0,
			'limit' => 0,
			'results' => $pathToTmp,
			'count' => 0,
			'status' => "ok",
			'message' => $target_file,
			]);

		
		return $this->byk("OK");
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

	public function pobierzRozszerzenie($typ)
	{
		$rozszerzenie = "xxx";
		switch($typ)
		{
			case "image/jpeg":  $rozszerzenie = "jpg"; break;
			case "image/jpg":  $rozszerzenie = "jpg"; break;
			case "image/png":  $rozszerzenie = "png"; break;
			case "image/svg+xml": $rozszerzenie = "svg"; break;
			case "image/svg": $rozszerzenie = "svg"; break;
			case "application/pdf": $rozszerzenie = "pdf"; break;
		}
		return $rozszerzenie;
	}

	function makeThumbnails($updir, $img, $id)
	{
		$thumbnail_width = 200;
		$thumbnail_height = 200;
		$thumb_beforeword = "thumb";
		$arr_image_details = getimagesize("$updir" . $id . '_' . "$img"); // pass id to thumb name
		$original_width = $arr_image_details[0];
		$original_height = $arr_image_details[1];
		if ($original_width > $original_height) {
			$new_width = $thumbnail_width;
			$new_height = intval($original_height * $new_width / $original_width);
		} else {
			$new_height = $thumbnail_height;
			$new_width = intval($original_width * $new_height / $original_height);
		}
		$dest_x = intval(($thumbnail_width - $new_width) / 2);
		$dest_y = intval(($thumbnail_height - $new_height) / 2);
		if ($arr_image_details[2] == IMAGETYPE_GIF) {
			$imgt = "ImageGIF";
			$imgcreatefrom = "ImageCreateFromGIF";
		}
		if ($arr_image_details[2] == IMAGETYPE_JPEG) {
			$imgt = "ImageJPEG";
			$imgcreatefrom = "ImageCreateFromJPEG";
		}
		if ($arr_image_details[2] == IMAGETYPE_PNG) {
			$imgt = "ImagePNG";
			$imgcreatefrom = "ImageCreateFromPNG";
		}
		if ($imgt) {
			$old_image = $imgcreatefrom("$updir" . $id . '_' . "$img");
			$new_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
			imagecopyresized($new_image, $old_image, $dest_x, $dest_y, 0, 0, $new_width, $new_height, $original_width, $original_height);
			$imgt($new_image, "$updir" . $id . '_' . "$thumb_beforeword" . "$img");
		}
	}	

}

