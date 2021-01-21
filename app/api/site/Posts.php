<?php

namespace app\api\site;

use app\lib\Model;

class Posts extends Model
{

    const PER_PAGE = 3;
    const LINK_LIMIT = 5;

    public function __construct()
    {
        parent::__construct();
    }

    public function pages()
    {
        $page = (isset($_GET['p'])) ? strip_tags($_GET['p']) : 1;

        $posts = $this->getConnection()->prepare("SELECT * FROM `website_posts`");
        $posts->execute();

        $posts_count = $posts->rowCount();
        $num_page = ceil($posts_count/self::PER_PAGE);

        $startIn = $page - 1;
        $startIn = ($startIn * self::PER_PAGE);

        $result = $this->getConnection()->prepare("SELECT * FROM `website_posts` ORDER BY `post_ID` DESC LIMIT {$startIn}, ".self::PER_PAGE);
        $result->execute();

        $fetch = $result->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fetch as $data)
        {
            $file = file_get_contents('./app/content/site/layouts/touse/post.phtml');

            $img = (empty($data['post_IMAGE'])) ? '' : "<img src='{$data['post_IMAGE']}' class='img-fluid'>";

            $html = str_replace([ '{title}', '{date}', '{hour}', '{body}', '{image}', '{author}', '{id}' ], [ $data['post_TITLE'], $this->convertDate($data['post_DATE']), date('H:i', strtotime($data['post_DATE'])), $this->limitBody($data['post_BODY'], 600, true), $img, $data['post_AUTHOR'], $data['post_ID']  ], $file);

            echo $html;
        }

        $li = "";

        $startLink = ((($page - self::LINK_LIMIT) > 1) ? $page - self::LINK_LIMIT : 1);
        $endLink = ((($page + self::LINK_LIMIT) < $num_page) ? $page + self::LINK_LIMIT : $num_page);

        if($num_page >= 1 && $page <= $num_page){
            for($i = $startLink; $i <= $endLink; $i++){

                if($i == $page){
                    $li .= "<li class=\"page-item active\"><a class=\"page-link\" href=\"javascript:void(0)\">{$i}</a></li>";
                } else {
                    $li .= "<li class=\"page-item\"><a class=\"page-link\" href=\"/home?p={$i}\">{$i}</a></li>";
                }

            }
        }

        $disableStart = ($page == 1) ? 'disabled' : '';
        $disableEnd = ($page == $num_page) ? 'disabled' : '';

        echo " <nav>
                  <ul class=\"pagination justify-content-center\">
                    <li class=\"page-item {$disableStart}\">
                      <a class=\"page-link\" href=\"/home\" tabindex=\"-1\">Início</a>
                    </li>
                    {$li}
                    <li class=\"page-item {$disableEnd}\">
                      <a class=\"page-link\" href=\"/home?p={$num_page}\">Último</a>
                    </li>
                  </ul>
                </nav>";
    }

    public function single($id)
    {
        $id = strip_tags(addslashes($id));

        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_posts` WHERE post_ID = ?");
        $stmt->bindValue(1, $id);
        $stmt->execute();

        if($stmt->rowCount() == 0)
        {
            return "<div class='col-md-12'>Postagem não encontrada</div>";
        }

        $fetch = $stmt->fetchObject();

        $date = date("d/m/Y à\s H:i", strtotime($fetch->post_DATE));

        return "

        <div class=\"announcement-header\">


        <span class=\"avatar avatar--s announcement-avatar\">
                <img draggable=\"false\" src=\"https://minotar.net/helm/{$fetch->post_AUTHOR}/64.png\" srcset=\"https://minotar.net/helm/{$fetch->post_AUTHOR}/64.png\" alt=\"{$fetch->post_AUTHOR}\" class=\"avatar-u2-s\"> 
        </span>
    
                        
    
                        <div class=\"announcement-header-details\">
    
                            <a class=\"announcement-title\">{$fetch->post_TITLE}</a>
    
                            <ul class=\"announcement-meta listInline listInline--bullet\">
    
                                <li>
                                    <i class=\"fa fa-user\" aria-hidden=\"true\" title=\"Postado por\"></i>
                                    <span>Postado por</span>
    
                                    <b>{$fetch->post_AUTHOR}</b>
                                </li>
    
                                <li>
                                    <i class=\"fas fa-clock\" aria-hidden=\"true\" title=\"Postado em\"></i>
                                    <span>em</span>
    
                                    <b>{$date}</b>
                                </li>
    
    
                            </ul>
    
                        </div>
    
                    </div>
         <img src=\"{$fetch->post_IMAGE}\" class=\"img-fluid\">
        <div class=\"body\">
        {$fetch->post_BODY}
        </div>
 ";
    }

    private function convertDate($date)
    {
        $date = new \DateTime($date);

        if(strtotime($date->format('Y-m-d')) == strtotime(date("Y-m-d")))
        {
            return "Hoje";
        }
        return $date->format('d/m/Y');
    }

    private function limitBody($texto, $limite, $quebrar = true){
        $contador = strlen(strip_tags($texto));
        if($contador <= $limite):
            $newtext = $texto;
        else:
            if($quebrar == true):
                $newtext = trim(mb_substr($texto, 0, $limite))."...";
            else:
                $ultimo_espaco = strrpos(mb_substr($texto, 0, $limite)," ");
                $newtext = trim(mb_substr($texto, 0, $ultimo_espaco))."...";
            endif;
        endif;
        return $newtext;
    }

}