<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Playbook extends MY_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->database();
    }
    
    /**
     * Load workboard
     */
    public function index()
    {
        set_time_limit(0);
        $start = date('Y-m-d H:i:s');
        $codes = $this->getCodes(20);
        
        if ($codes) {
            foreach ($codes as $code) {
                $ch = curl_init('http://covsys.net/GoPlayBooksService/Report.svc/GetYearlyReport/' . $code->playbook_code . '/All/' . date('Y'));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: 0'));
                curl_setopt($ch, CURLOPT_POST, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $output = curl_exec($ch);
                curl_close($ch);
                
                $data = json_decode($output);
                $data = (array)$data[0];
                
                $this->saveReport($data, $code);
                
                $this->saveIngredients($data, $code);
                
                $this->db->where('id', $code->id);
                $this->db->update('playbook_code', array(
                    'cron_updated_at' => date('Y-m-d'),
                    'ran_by' => 1
                ));
            }
            
            $end = date('Y-m-d H:i:s');
            $do_email = array();
            $do_email['from'] = 'tasktracker@asb.club';
            $do_email['to'] = 'jaime@advancedscoreboards.com, tran.pham@ceresolutions.com';
            $do_email['subject'] = "Playbook Cronjob";
            $do_email['html_body'] = $this->cronjob_report($codes, $start, $end);
            $do_email['reply_to'] = 'jaime@advancedscoreboards.com';
            $do_email['Attachments'] = array();
            
            $res = $this->send_email($do_email);
        }
        
        echo 'Successfull';
    }
    
    private function cronjob_report($codes, $start, $end) {
        $message = '';
        $message .="<head>";
        
        $message .="</head>";
        
        $message .= "<body style='font-family: Times New Roman, Georgia, serif; font-size: 12pt; width: 100%;'>";
        
        $message .= "<h3 style='background-color: #808080; color: white; line-height: 50px; padding-left: 10px;'>Playbook Chemical Cronjob has run successfully at " . date('Y-m-d H:i') . "</h3>";
        $message .= "<h34style='background-color: #808080; color: white; line-height: 50px; padding-left: 10px;'>Started at: " . $start . ", Ended at: " . $end . "</h4>";
        $message .= "<div style='margin-left: 10px; width: 100%;'>";
        
        if ($codes) {
            $message .= "<div text-align='center'>Updated Codes:</div>";
            $message .= "<table>";
            $message .= "<thead><th><td>Company</td><td>Playbook Code</td></th></thead>";
            $message .= "<tbody>";
            foreach ($codes as $code) {
                $message .= "<tr><td>" . $code->c_name . "</td><td>" . $code->playbook_code . "</td></tr>";
            }
            $message .= "</tbody>";
            $message .= "</table'>";
        } else {
            $message .= "<div text-align='center'>No codes updated.</div>";
        }
        
        $message .= "<span>";
        $message .= "The ASB taskTracker Team.<br />";
        $message .= "</span>";
        
        $message .= "</div>";
        
        $message .= "</body>";
        $message .= "</html>";
        return $message;
    }
    
    private function saveIngredients($data, $code)
    {
        $nutrients = isset($data['Nutrients']) ? $data['Nutrients'] : array();
        foreach ($nutrients as $nutrient) {
            $areaname = $nutrient->AreaName;
            $ingredients = (array)$nutrient->Ingredients;
            
            if ($ingredients) {
                foreach ($ingredients as $ingredient => $value) {
                    $chemical_id = $this->getChemical($ingredient);
                    if (!$chemical_id) {
                        continue;
                    }
                    
                    $playbook_nutrient_id = $this->getPlaybookNutrient($code->id, $areaname, $chemical_id);
                    $fields = array(
                        'ytd' => floatval($value->YTD),
                        'updated_at' => time()
                    );
                    if ($playbook_nutrient_id) {
                        $this->db->where('id', $playbook_nutrient_id);
                        $this->db->update('playbook_nutrient', $fields);
                    } else {
                        $fields['chemical_id'] = $chemical_id;
                        $fields['areaname'] = $areaname;
                        $fields['playbook_code_id'] = $code->id;
                        $fields['year'] = date('Y');
                        $this->db->insert('playbook_nutrient', $fields);
                    }
                }
            }
        }
    }
    
    private function saveReport($data, $code)
    {
        $totalCost = isset($data['ApplicationTotalCost']) ? $data['ApplicationTotalCost'] : null;
        $gdd32 = isset($data['GDD32']) ? $data['GDD32'] : null;
        $gdd50 = isset($data['GDD50']) ? $data['GDD50'] : null;
        $eiq = isset($data['GDD50']) ? $data['EIQ'] : null;
        $alerts_badge = isset($data['New Alerts Badge']) ? $data['New Alerts Badge'] : null;
        
        $playbook_report_id = $this->getPlaybookReport($code->id);
        
        $fields = array(
            'application_total_cost' => $totalCost ? $totalCost->YTD : 0,
            'gdd32' => $gdd32 ? $gdd32->YTD : 0,
            'gdd50' => $gdd50 ? $gdd50->YTD : 0,
            'eiq' => $eiq ? $eiq->YTD : 0,
            'alerts_badge' => $alerts_badge ? $alerts_badge->Number : 0,
            'updated_at' => time()
        );
        if ($playbook_report_id) {
            $this->db->where('id', $playbook_report_id);
            $this->db->update('playbook_report', $fields);
        } else {
            $fields['playbook_code_id'] = $code->id;
            $fields['year'] = date('Y');
            $this->db->insert('playbook_report', $fields);
        }
    }
    
    private function getPlaybookReport($playbook_code_id)
    {
        $this->db->select('id');
        $this->db->from('playbook_report');
        $this->db->where('playbook_code_id', $playbook_code_id);
        $this->db->where('year', date('Y'));
        $this->db->limit(1);
        $query = $this->db->get();
        $rows = $query->result();
        
        if ($rows) {
            $row = $rows[0];
            return $row->id;
        }
        
        return null;
    }
    
    private function getPlaybookNutrient($playbook_code_id, $areaname, $chemical_id)
    {
        $this->db->select('id');
        $this->db->from('playbook_nutrient');
        $this->db->where('playbook_code_id', $playbook_code_id);
        $this->db->where('areaname', $areaname);
        $this->db->where('chemical_id', $chemical_id);
        $this->db->where('year', date('Y'));
        $this->db->limit(1);
        $query = $this->db->get();
        $rows = $query->result();
        
        if ($rows) {
            $row = $rows[0];
            return $row->id;
        }
        
        return null;
    }
    
    private function getChemical($playbook_nutrient)
    {
        $this->db->select('id');
        $this->db->from('playbook_chemical');
        $this->db->where('chemical', $playbook_nutrient);
        $this->db->limit(1);
        $query = $this->db->get();
        $rows = $query->result();
        if ($rows) {
            $row = $rows[0];
            return $row->id;
        }
        
        return null;
    }
    
    private function getCodes($limit = 1000)
    {
        $this->db->select('playbook_code.id, playbook_code.playbook_code, companies.c_name');
        $this->db->from('playbook_code');
        $this->db->join('companies', 'companies.company_id = playbook_code.c_id', 'LEFT OUTER');
        $this->db->where('(cron_updated_at IS NULL OR cron_updated_at < "' . date('Y-m-d') . '")', null);
        $this->db->where('playbook_code.remove', 0);
        $this->db->order_by('id');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        
        return $query->result();
    }
}