<?php

namespace app\api;

use app\lib\Json;
use app\lib\Model;
use app\lib\Security;

class Changelogs
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
            return Json::encode(['status' => 'error', 'message' => 'Conexão bloqueada']);
        }
        if(empty($_POST))
        {
            return Json::encode(['status' => 'error', 'message' => 'Nenhuma requisição solicitada']);
        }
        if (empty($_POST['title']) || empty($_POST['topic']))
        {
            return Json::encode(['status' => 'error', 'message' => 'Insira todos os campos']);
        }

        $stmt = $this->model->getConnection()->prepare("INSERT INTO `website_posts_changelogs`(`changelog_TITLE`, `changelog_TOPIC`, `changelog_DATE`) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['title'], $_POST['topic'], date("Y-m-d H:i:s")]);

        return Json::encode(['status' => 'ok', 'message' => 'Adicionado com sucesso']);
    }

    public function delete()
    {
        if (!Security::ajax()) {
            return Json::encode(['status' => 'error', 'message' => 'Conexão bloqueada']);
        }
        if (empty($_POST)) {
            return Json::encode(['status' => 'error', 'message' => 'Nenhuma requisição solicitada']);
        }

        $stmt = $this->model->getConnection()->prepare("DELETE FROM `website_posts_changelogs` WHERE `changelog_ID` = ?");
        $stmt->execute([$_POST['id']]);

        return Json::encode(['status' => 'ok', 'message' => 'Deletado com sucesso']);
    }

    public function edit()
    {
        if (!Security::ajax()) {
            return Json::encode(['status' => 'error', 'message' => 'Conexão bloqueada']);
        }
        if (!isset($_POST)) {
            return Json::encode(['status' => 'error', 'message' => 'Nenhuma requisição solicitada']);
        }

        $stmt = $this->model->getConnection()->prepare("UPDATE `website_posts_changelogs` SET `changelog_TOPIC`=? WHERE `changelog_ID`=?");
        $stmt->execute([$_POST['topic'], $_POST['id']]);

        return Json::encode(['status' => 'ok']);
    }

    public function count()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_posts_changelogs`");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function listAll()
    {
        $changelogs = "";
        $dates = $this->getDates();
        $i = 0;

        foreach ($dates as $rsd => $date) {
            $titles = $this->getTitles($date);
            $datef = date("d/m/Y", strtotime($date));
            $changelogers = "<ul class=\"atualizacoes-list\">";
            foreach ($titles as $rst => $title) {
                $changelogers .= "<div class=\"title\">{$title->changelog_TITLE}</div>";
                $topics = $this->getTopics($title->changelog_TITLE, $date);
                foreach ($topics as $topic) {
                    $changelogers .= " <li class='input-box-{$topic['id']}'>
                                          <form method=\"post\" class=\"editChange\">
                                            <input name=\"id\" value=\"{$topic['id']}\" type=\"hidden\">
                                            <div class=\"input-box\">
                                              <input name=\"topic\" value=\"{$topic['topic']}\">
                                              <div class=\"button-box\">
                                                <button class=\"edit\"><i class=\"fa fa-edit\"></i></button>
                                                <button type=\"button\" class=\"delete del-change\" id='{$topic['id']}'><i class=\"fa fa-remove\"></i></button>
                                              </div>
                                            </div>
                                          </form>
                                        </li>";
                }
            }
            $changelogers .= "</ul>";
            $changelogs .= "<tr>
                                <td>{$datef}</td>
                                <td><button class='btn btn-primary btn-sm btn-block' data-toggle=\"modal\" data-target=\"#modal-{$i}\">abrir</button></td>
                            </tr>
                            <div class=\"modal fade\" id=\"modal-{$i}\" tabindex=\"-1\" role=\"dialog\">
                              <div class=\"modal-dialog\" role=\"document\">
                                <div class=\"modal-content\">
                                  <div class=\"modal-header\">
                                    <h5 class=\"modal-title\">Editar</h5>
                                    <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">
                                      <span aria-hidden=\"true\">&times;</span>
                                    </button>
                                  </div>
                                  <div class=\"modal-body\">
                                    {$changelogers}
                                  </div>
                                </div>
                              </div>
                            </div>";
        }
        return $changelogs;
    }

    public function printall()
    {
        $datas  = $this->getDates();

        if(count($datas) == 0) { return "<h3 class='text-muted text-center'>Não há atualizações</h3>"; }

        $qnt    = 100;
        $atual  = (isset($_GET['p'])) ? intval($_GET['p']) : 1;
        rsort($datas);
        $pag    = array_chunk($datas, $qnt);
        $count  = count($pag);
        $result = $pag[$atual-1];

        $return = "<div class='changelog'>";
        foreach ($result as $data)
        {
            $date = date('d/m/Y', strtotime($data));

            if($date == date("d/m/Y")) { $date = "Hoje"; }

            $return .= "<div class=\"row justify-content-center\">
                        <div class='col-md-2 col-md-offset-1'>
                            <h3 class='changelog-date'>{$date}</h3>
                        </div> 
                        <div class='col-md-7'>
                        <div class='changelog-group'>";
            $titles = $this->getTitles($data);
            foreach ($titles as $rst => $title)
            {
      

                $return .= "<h4 class=\"changelog-category\">{$title->changelog_TITLE}</h4><ul class=\"changelog-list\">";
                $topics = $this->getTopics($title->changelog_TITLE, $data);
                $topicos = "";
                foreach ($topics as $topic)
                {
                    $topicos .= "<li>{$topic['topic']}</li>";
                }
                $return .= "{$topicos}</ul></ul>";
            }
            $return .= "</div></div></div>";
        }

        $pags = "";
        for($i = 1; $i <= $count; $i++)
        {
            if($i == $atual) {
                $pags .= "";
            }else{
                $pags .= "";
            }
        }
        $pagination = "";
        return $return."</div>".$pagination;
    }

    private function getDates()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT `changelog_DATE` FROM `website_posts_changelogs` ORDER BY `changelog_ID` DESC");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $array = [];
        foreach ($fetch as $rs)
        {
            $date = explode(' ', $rs->changelog_DATE);
            $array[] = $date[0];

        }
        return array_unique($array);
    }

    private function getTitles($date)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT DISTINCT `changelog_TITLE` FROM `website_posts_changelogs` WHERE `changelog_DATE` LIKE '%{$date}%'");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function getTopics($title, $date)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT `changelog_TOPIC`, `changelog_ID` FROM `website_posts_changelogs` WHERE `changelog_DATE` LIKE '%$date%' AND `changelog_TITLE` LIKE '%$title%'");
        $stmt->execute();
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $topics = [];
        foreach ($fetch as $rs)
        {
            array_push($topics,
                [
                    'id'    => $rs->changelog_ID,
                    'topic' => $rs->changelog_TOPIC
                ]
            );
        }
        return $topics;
    }

    private function tables()
    {
        $stmt = $this->model->getConnection()->prepare("CREATE TABLE `website_posts_changelogs` ( `changelog_ID` INT(11) NOT NULL AUTO_INCREMENT ,  `changelog_TITLE` TEXT NOT NULL ,  `changelog_TOPIC` TEXT NOT NULL ,  `changelog_DATE` DATETIME NOT NULL ,    PRIMARY KEY  (`changelog_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }
}