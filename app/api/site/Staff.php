<?php

namespace app\api\site;

use app\lib\Model;

class Staff extends Model
{

    public function __construct()
    {
        parent::__construct();
    }

    public function show()
    {
        $offices = $this->getOffices();
        $print = "";

        foreach ($offices as $office)
        {
            $print .= "<div class=\"staff conteudo\" style='margin-bottom: 20px; padding-bottom: 20px;'>
                            <div class=\"body\">
                                <h4 class='mb-2' style='color: {$office->staff_COLOR}; font-size: 22px; font-weight: 600;'>{$office->staff_NAME} ({$this->countMembers($office->staff_ID)})</h4>
                                <div class=\"row\">";

            $members = $this->getMembers($office->staff_ID);

            foreach ($members as $member)
            {
                $link = (!empty($member->member_TWITTER)) ? $member->member_TWITTER : 'javascript:void(0)';

                $print .= "<div class=\"col-md-3\" style='margin-top: 20px;'>
                                 <a href='{$link}' target='_blank'>
                                    <div class='member'>
                                        <p style='text-align: center; margin-bottom: 5px;'><img src=\"https://minotar.net/avatar/{$member->member_NAME}/100.png\" style='border-radius: 10px;' data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"{$member->member_TWITTER}\"></p>
                                        <p style='text-align: center; font-weight: 600; margin: 0; padding: 0;'>{$member->member_NAME}</p>
                                    </div>
                                </a>
                            </div>";
            }

            $print .= " </div>
                    </div>
                </div>";
        }

        return $print;
    }

    private function getMembers($office)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_staffs_members` WHERE `member_OFFICE` = ?");
        $stmt->execute([$office]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function getOffices()
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_staffs`");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function countMembers($office)
    {
        $stmt = $this->getConnection()->prepare("SELECT * FROM `website_staffs_members` WHERE `member_OFFICE` = ?");
        $stmt->execute([$office]);
        return $stmt->rowCount();
    }


}