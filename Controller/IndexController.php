<?php
namespace Alxishin\LogViewBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LogViewController extends Controller{
	
	public function indexAction(Request $request, $verbosity, $env = 'prod', $limit = 100) {
		$this->title = 'System Logs';
		$logpath = $this->get('kernel')->getRootDir().'/logs/'.$env.'.log';
		if(!is_file($logpath)){
			return $this->render('AishinLogViewBundle:index:index.html.twig',array());
		}
		$content = [];
		$fp = fopen($logpath, "r");
		while(!feof($fp))
		{
			$line = fgets($fp);
			if(!$line){
				continue;
			}
			array_push($content, $line);
			if (count($content) > $limit) {
				array_shift($content);
			}
		}
		$logs = array();
		foreach($content as $key=>$val){
			$item = json_decode($val, true);
			if(json_last_error()!==JSON_ERROR_NONE){
				throw new \Exception(json_last_error_msg());
			}
			if(empty($item['message']) || empty($item['datetime']['date'])){
				continue;
			}
            if($item['level'] < $verbosity){
                continue;
            }
			if(isset($item['extra']['user_id'])){
				$employee = $this->getDoctrine()
					->getRepository('AcmeProjectBundle:Employee')->findOneById($item['extra']['user_id']);
				$item['user'] = '<a href="'.$this->get('router')->generate('admin_acme_project_employee_show',array('id'=>$employee->getId())).'">'.$employee->getFio().'</a>';
			}
			$item['details'] = print_r($item['extra']/*array($item['context'], $item['extra'])*/, true);
			$item['background_color'] = $this->logLevels[$item['level']];
			$item['tr_class'] = $this->table_colors[$item['level']];
			$logs[] = $item;
		}
		
		$logs = array_reverse($logs);
		
		$routeParams = [
			'verbosity'=>[500,400,0],
			'env'=>['prod','dev','test'],
			'limit'=>[10,100,1000,10000],
		];
		return $this->render('AishinLogViewBundle:index:index.html.twig', array('logs'=>$logs,'routeParams'=>$routeParams));
	}
	
	private $logLevels = array(
        \Monolog\Logger::DEBUG     => '#cccccc',
        \Monolog\Logger::INFO      => '#468847',
        \Monolog\Logger::NOTICE    => '#3a87ad',
        \Monolog\Logger::WARNING   => '#c09853',
        \Monolog\Logger::ERROR     => '#f0ad4e',
        \Monolog\Logger::CRITICAL  => '#FF7708',
        \Monolog\Logger::ALERT     => '#C12A19',
        \Monolog\Logger::EMERGENCY => '#000000',
    );
	
	private $table_colors = array(
		\Monolog\Logger::DEBUG     => '',
        \Monolog\Logger::INFO      => '',
        \Monolog\Logger::NOTICE    => '',
        \Monolog\Logger::WARNING   => 'info',
        \Monolog\Logger::ERROR     => 'info',
        \Monolog\Logger::CRITICAL  => 'error',
        \Monolog\Logger::ALERT     => 'error',
        \Monolog\Logger::EMERGENCY => 'error',
	);
}
