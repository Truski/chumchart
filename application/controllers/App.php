<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class App extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('chumbase');
	}

	public function index()
	{
		$this->load->view('header');
		$this->load->view('home');
		$this->load->view('footer');
	}

	public function quiz($urlid = NULL)
	{
		$data = array();
		$data['hasURL'] = ($urlid != NULL);
		if($urlid != NULL){
			$data['urlid'] = $urlid;
		}
		$this->load->view('header');
		$this->load->view('quiz', $data);
		$this->load->view('footer');
	}

	public function table($urlid = NULL)
	{
		
		$this->load->view('header');
		$this->load->view('chart');
		$this->load->view('footer');
	}

	private function createChart($urlid) {

		// Get all surveys with corresponding urlid
		$surveys = $this->chumbase->getSurveys($urlid);
		
		$ethics = array_fill(0, count($surveys), 0);
		$morality = array_fill(0, count($surveys), 0);
		$id = array_fill(0, count($surveys), 0);
		for ($i = 0; $i < count($surveys); $i++) {
			$ethics[$i] = $surveys[$i]->ethics;
			$morality[$i] = $surveys[$i]->morality;
			$id[$i] = $surveys[$i]->id;
		}

		$align = new ScoreToAlignment($ethics, $morality, $id);
		$best = $align->go();
		$reassigned = array_fill(0, count($surveys), 0);
		for ($i = 0; $i < count($best); $i++) {
			for ($j = 0; $j < count($surveys); $j++) {
				if ($best[$i]->id == $surveys[$j]->id) {
					$reassigned[$i] = $surveys[$j];
					break;
				}
			}
		}

		$this->chumbase->fillChart($urlid,
			$reassigned[2]->id,
			$reassigned[5]->id,
			$reassigned[8]->id,
			$reassigned[1]->id,
			$reassigned[4]->id,
			$reassigned[7]->id,
			$reassigned[0]->id,
			$reassigned[3]->id,
			$reassigned[6]->id
		);

	}

	public function submitData() {
		$json = $this->input->post("data");
		$obj = json_decode($json);

		$name = $obj->name;
		$urlid = $obj->urlid;

		$dataObj = new dataToScore($obj->answers);
		$eScore = $dataObj->eScore;
		$mScore = $dataObj->mScore;

		// Send survey data to database
		$urlid = $this->chumbase->insertQuiz($urlid, $mScore, $eScore, $name);

		// Ask server for number of users in urlid
		$numSurveys = $this->chumbase->getUserCount($urlid);

		// If there's 9 surveys, create the alignment chart!
		if ($numSurveys == 9) {
			$this->createChart($urlid);
		}
		$result = new stdclass;
		$result->remaining = 9 - $numSurveys;
		echo json_encode($result);
	}

	public function getChart($urlid) {
		// Call model function
		$all = getChart($urlid);

		//get 9 categories and associating user id
		$data = array();
		$data['lg'] = $all->lg;
		$data['ln'] = $all->ln;
		$data['lc'] = $all->lc;
		$data['ng'] = $all->ng;
		$data['tn'] = $all->tn;
		$data['ne'] = $all->ne; 
		$data['cg'] = $all->cg;
		$data['cn'] = $all->cn;
		$data['ce'] = $all->ce;
		$this->load->view('chart', $data);	
	}

}
?>
