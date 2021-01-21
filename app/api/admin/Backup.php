<?php

namespace app\api\admin;

use app\lib\Config;
use app\lib\Json;
use app\lib\Model;
use app\lib\Security;
use Ifsnop\Mysqldump\Mysqldump;

class Backup extends Config
{
    private $model;

    public function __construct()
    {
        $this->model = new Model();
        $this->table();
    }

    public function manual()
    {
        if(!Security::ajax())
        {
            return Json::encode(['response' => 'error', 'message' => 'Conexão recusada!']);
        }
          $this->save();
        return Json::encode([ 'response' => 'ok', 'message' => 'Backup realizado com sucesso' ]);
    }

    public function download($id)
    {
        if(!Security::ajax())
        {
            echo Json::encode(['response' => 'ERROR!', 'ARQUIVO BLOQUEADO!' ]);
        }

        set_time_limit(0);

        $info = $this->info($id);

        $name = $info['file'];
        $path = $info['path'].$name;

        $this->addDownload($id);

        if (!file_exists($path)) {
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="'.basename($path).'"');
        header('Content-Type: application/zip');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Expires: 0');
        readfile($path);
    }

    public function save()
    {

        $file = "db-".date("YmdHis").".sql.gz";
        $path = "./backups/$file";

        try {
            $dump = new Mysqldump("mysql:host=".self::DBHOST.";dbname=".self::DBNAME, self::DBUSER, self::DBPASS, array(
                'compress' => Mysqldump::GZIP,
                'exclude-tables' => [ 'website_settings_databases' ]
            ));
            $dump->start($path);

            $this->addLog($path);
            return $path;
        }catch (\Exception $e)
        {
            return "error ".$e->getMessage();
        }

    }

    public function logs()
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_backup`");
        $stmt->execute();
        $table = "";
        $fetch = $stmt->fetchAll(\PDO::FETCH_OBJ);
        foreach ($fetch as $result) {
            $date = date("d/m/Y H:i", strtotime($result->backup_DATE));
            $table .= "<tr>
                        <td>{$result->backup_ID}</td>
                        <td>{$date}</td>
                        <td>{$result->backup_DOWNLOADS}</td>
                        <td><center><button class=\"btn btn-sm btn-primary backup-download\" id='{$result->backup_ID}'><i class=\"fa fa-download\"></i></button></center></td>
                    </tr>";
        }
        return $table;
    }

    private function addLog($path)
    {
        $stmt = $this->model->getConnection()->prepare('INSERT INTO `website_settings_backup`(`backup_DATE`, `backup_DIR`) VALUES (?, ?)');
        $stmt->execute([ date("Y-m-d H:i:s"), $path ]);
    }

    private function info($id)
    {
        $stmt = $this->model->getConnection()->prepare("SELECT * FROM `website_settings_backup` WHERE `backup_ID`=?");
        $stmt->execute([$id]);
        $fetch = $stmt->fetchObject();

        $file = explode('./backups/', $fetch->backup_DIR)[1];

        return [
            'file' => $file,
            'path' => './backups/'
        ];
    }

    private function addDownload($id)
    {
        $stmt = $this->model->getConnection()->prepare("UPDATE `website_settings_backup` SET `backup_DOWNLOADS`=`backup_DOWNLOADS`+1 WHERE `backup_ID`=?");
        $stmt->execute([$id]);
    }

    private function table()
    {
        $stmt = $this->model->getConnection()->prepare("CREATE TABLE IF NOT EXISTS `website_settings_backup` ( `backup_ID` INT(11) NOT NULL AUTO_INCREMENT , `backup_DATE` DATETIME NOT NULL , `backup_DIR` TEXT NOT NULL , `backup_DOWNLOADS` INT(11) NOT NULL DEFAULT '0' , PRIMARY KEY (`backup_ID`)) ENGINE = InnoDB;");
        $stmt->execute();
    }
}