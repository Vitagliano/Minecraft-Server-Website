<?php

namespace app\api\admin;

use app\api\Images;
use app\lib\Forms;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Posts
{

    

    private $model;

    public function __construct()
    {
        $this->model = new Model();
        $this->tables();
    }

    public function add()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(Forms::isEmpty($_POST, [ 'title', 'author', 'body' ])) {
            return Json::encode([ 'response' => 'error', 'message' => 'Informe todos os campos' ]);
        }
        if(!isset($_FILES['image']))
        {
            return Json::encode([ 'response' => 'error', 'message' => 'Selecione uma imagem' ]);
        }
        $images = new Images();

        $image = $images->save($_FILES['image'], "./app/content/site/assets/images/posts/");
        $image = Json::decode($image);

        if(!$image->response)
        {
            return Json::encode([ 'response' => 'error', 'message' => $image->message ]);
        }

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_posts`(`post_TITLE`, `post_AUTHOR`, `post_DATE`, `post_IMAGE`, `post_BODY`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([ $_POST['title'], $_POST['author'], date("Y-m-d H:i:s"), $image->src, $_POST['body'] ]);

        return Json::encode(['response' => 'ok', 'message' => 'Publicado com sucesso!']);
    }

    public function delete()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }

        $this->model->getConnection()->prepare("DELETE FROM `website_posts` WHERE `post_ID`=?")->execute([$_POST['id']]);

        return Json::encode(['response' => 'ok']);
    }

    public function edit()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
        if(empty($_POST))
        {
            return Json::encode(['response' => 'error', 'message' => 'Global POST não está definida']);
        }
        if(Forms::isEmpty($_POST, [ 'title', 'author', 'body' ])) {
            return Json::encode([ 'response' => 'error', 'message' => 'Informe todos os campos' ]);
        }
        $src = "";
        if(isset($_FILES['image']))
        {
            $images = new Images();

            $image = $images->save($_FILES['image'], "./app/content/site/assets/images/posts/");
            $image = Json::decode($image);

            if(!$image->response)
            {
                return Json::encode([ 'response' => 'error', 'message' => $image->message ]);
            }

            $src = $image->src;
        }

        $stmt = $this->model->getConnection()->prepare("UPDATE `website_posts` SET `post_TITLE`=?,`post_AUTHOR`=?, `post_IMAGE`=?,`post_BODY`=? WHERE `post_ID`=?");
        $stmt->execute([ $_POST['title'], $_POST['author'], $src, $_POST['body'], $_POST['id'] ]);

        return Json::encode([ 'response' => 'ok', 'message' => 'Postagem editada com sucesso' ]);
    }

    public function table()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_posts`");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";
        foreach ($fetch as $rs)
        {
            $table .= "<tr>
                            <th scope=\"row\">{$rs->post_ID}</th>
                            <td>{$rs->post_TITLE}</td>
                            <td>{$rs->post_AUTHOR}</td>
                            <td>{$this->limitBody($rs->post_BODY)}</td>
                            <td>{$rs->post_DATE}</td>
                            <td><button class=\"btn btn-sm btn-primary\" data-toggle=\"modal\" data-target=\"#md-{$rs->post_ID}\"><i class=\"fa fa-edit\"></i></button> <button class=\"btn btn-sm btn-danger post-delete\" id='{$rs->post_ID}'><i class=\"fa fa-remove\"></i></button></td>
                            <div class=\"modal fade\" id=\"md-{$rs->post_ID}\" tabindex=\"-1\" role=\"dialog\">
                                <div class=\"modal-dialog modal-lg\" role=\"document\">
                                    <div class=\"modal-content\">
                                        <div class=\"modal-header\"> 
                                            <h5 class=\"modal-title\">Editar postagem</h5>
                                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                                <span aria-hidden=\"true\">&times;</span>
                                            </button>
                                        </div>
                                        <div class=\"modal-body\">
                                            <form method=\"post\" enctype=\"multipart/form-data\" class=\"editPost\">
                                                <input type='hidden' name='id' value='{$rs->post_ID}'>
                                                <div class=\"form-group\">
                                                    <label>Título</label>
                                                    <input class=\"form-control\" name=\"title\" value='{$rs->post_TITLE}' autocomplete=\"false\">
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Autor</label>
                                                    <input class=\"form-control\" name=\"author\" value='{$rs->post_AUTHOR}'>
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Imagem do cabeçalho</label>
                                                    <div class=\"input-group mb-3\">
                                                        <div class=\"custom-file\">
                                                            <input type=\"file\" class=\"custom-file-input\" id=\"inputGroupFile02\" name=\"image\">
                                                            <label class=\"custom-file-label\" for=\"inputGroupFile02\">Escolha a imagem</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class=\"form-group\">
                                                    <label>Notícia</label>
                                                    <textarea class=\"summernote\" name=\"body\">{$rs->post_BODY}</textarea>
                                                </div>
                                                <button class=\"btn btn-primary\">Atualizar postagem</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tr>";
        }
        return $table;
    }

    public function count()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_posts`");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function recents()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_posts` ORDER BY `post_ID` DESC LIMIT 2");
        $stmt->execute();

        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $table = "";

        foreach ($fetch as $result) {
            $table .= "<div class=\"col-md-6\">
                        <div class=\"card\">
                            <a href=\"/home/postagem/{$result->post_ID}\">
                                <img class=\"card-img-top img-fluid w-100\" src=\"{$result->post_IMAGE}\" alt=\"\">
                            </a>
                            <div class=\"card-body\">
                                <h6 class=\"card-title mb-1\"><a href=\"/home/postagem/{$result->post_ID}\">{$result->post_TITLE}</a></h6>
                                <p class=\"card-text small\">
                                    {$this->limitBody($result->post_BODY)}
                                </p>
                            </div>
                            <hr class=\"my-0\">
                            <div class=\"card-footer small text-muted\">
                                Postado em {$this->toDateTime($result->post_DATE)}
                            </div>
                        </div>
                        <br>
                    </div>";
        }
        return $table;
    }

    private function limitBody($str)
    {
        $str = strip_tags($str);
        if (strlen($str) > 60)	{
            $var = substr($str, 0, 60);
            $var = trim($var) . "...";
            return $var;
        }
        return $str;
    }

    private function toDateTime($date)
    {
        return date("d/m/Y à\s\ H:i", strtotime($date));
    }

    private function tables()
    {
        $stmt = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_posts` ( `post_ID` INT(11) NOT NULL AUTO_INCREMENT , `post_TITLE` VARCHAR(50) NOT NULL , `post_AUTHOR` VARCHAR(50) NOT NULL , `post_DATE` DATETIME NOT NULL , `post_IMAGE` VARCHAR(255) NOT NULL, `post_BODY` TEXT NOT NULL , PRIMARY KEY (`post_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }

}