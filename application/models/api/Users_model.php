<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/core/MY_API_Model.php';

class Users_model extends MY_API_Model {

    public function __construct() {

        parent::__construct();
    }

    /**
     * Check user exist for login API
     *
     */
    public function get_user_detail($user_name, $password) {
        $this->db->select('u.*');
        $this->db->from('users u');
        $this->db->where(array('u.username' => $user_name, 'u.password' => md5($password)));
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    /**
     * Check user exist for login API
     *
     */
    public function get_user_detail_by_id($request_data) {
        $this->db->select('u.*');
        $this->db->from('users u');
        $this->db->where(array('u.id' => $request_data['user_id']));
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    /**
     * Verify OTP of user
     *
     */
    public function user_verify_otp($user_id, $otp) {
        $this->db->select('id,concat(first_name," ",last_name)as name,username,email,otp');
        $this->db->from('core_users');
        $this->db->where(array('id' => $user_id, 'otp' => $otp, 'usertype_id' => API_MOU_USERTYPE_ID, 'deleted_by' => 0, 'status' => 0));
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    /**
     * get user detail by email
     *
     * @access    public
     * @param    email
     * @return    record count
     */
    public function get_user_detail_by_email($email) {

        //Get count user by email id
        $this->db->where('email', $email);
        $this->db->where('deleted_by', '0');
        $this->db->from('core_users');
        return $num_res = $this->db->count_all_results();
    }

    /**
     * Update user details
     *
     * @access    public
     * @param    user details array, email
     * @return    true / false
     */
    public function set_forgot_password_code($data, $email) {

        //set forgot password into table
        $this->db->where('email', $email);
        return $this->db->update('core_users', $data);
    }

    /**
     * Get today catch count
     *
     * @access    public
     * @param    NA
     * @return    Object
     */
    public function get_today_catch_count($user_id, $last_days) {

        $this->db->select('IFNULL(sum(cls.catch_qty),0) as total_catch_qty,DATE(cl.entry_date) as entry_date');
        $this->db->from('core_catch_logsheet_species cls');
        $this->db->join('core_catch_logsheets cl', 'cl.id = cls.catch_logsheet_id');
        $this->db->where(array('cl.entry_by' => $user_id, 'cls.deleted_by' => 0, 'cl.deleted_by' => 0));
        $this->db->where('DATE(cl.entry_date) BETWEEN DATE_SUB(CURDATE(),INTERVAL ' . $last_days . ' DAY) AND CURDATE()');
        $this->db->group_by('cl.entry_date');
        $this->db->order_by('cl.entry_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get today sold count
     *
     * @access    public
     * @param    $user_id , $last_days
     * @return    Object
     */
    public function get_today_sold_count($user_id, $last_days) {

        $this->db->select('IFNULL(sum(pls.purchase_qty),0) as total_sold_qty, pl.purchase_date');
        $this->db->from('core_purchase_logsheet_species pls');
        $this->db->join('core_purchase_logsheets pl', 'pl.id = pls.purchase_logsheet_id');
        $this->db->where(array('pl.entry_by' => $user_id, 'pls.deleted_by' => 0, 'pl.deleted_by' => 0));
        $this->db->where('pl.purchase_date BETWEEN DATE_SUB(CURDATE(),INTERVAL ' . $last_days . ' DAY) AND CURDATE()');
        $this->db->group_by('pl.purchase_date');
        $this->db->order_by('pl.purchase_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get voyages list
     *
     * @access    public
     * @param    $request_data
     * @return    Object
     */
    public function get_voyages_list($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $limit = API_PAGE_LIMIT;
        $offset = ($request_data['page_no'] - 1) * $limit;
        $offset = ($offset > 0) ? $offset : 0;

        $this->db->select('v.vessel_regno, v.vessel_name, count(cl.vessel_id) AS voyage_count');
        $this->db->from("core_catch_logsheets AS cl");
        $this->db->join("core_vessels v", "v.id = cl.vessel_id", "left");
        $this->db->where(array('cl.entry_by' => $user_id, 'cl.deleted_by' => 0, 'v.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        $this->db->group_by("cl.vessel_id");

        $query = $this->db->get_compiled_select();
        $this->db->from("(" . $query . ") as r1");
        if ($request_data['page_no'] != -1) {
            $this->db->limit($limit, $offset);
        }

        $returnArr['voyages_list'] =  $this->db->get()->result();

        if ($request_data['page_no'] == -1) {
            $returnArr['next_page_no'] = 0;
        } else {
            // Check is data has for next page
            $this->db->select('vessel_regno');
            $this->db->from("(" . $query . ") as r2");
            $offset = ($request_data['page_no']) * $limit;
            $offset = ($offset > 0) ? $offset : 0;
            $this->db->limit($limit, $offset);
            $next_result = $this->db->get()->result_array();
            $returnArr['next_page_no'] = (count($next_result) > 0) ? $request_data['page_no'] + 1 : 0;
        }
        return $returnArr;
    }

    /**
     * Get voyages total count
     *
     * @access   public
     * @param    $request_data
     * @return   Object
     */
    public function get_total_voyages_count($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $this->db->select('count(*) AS total_voyage_count');
        $this->db->from("core_catch_logsheets AS cl");
        $this->db->join("core_vessels v", "v.id = cl.vessel_id", "left");
        $this->db->where(array('cl.entry_by' => $user_id, 'cl.deleted_by' => 0, 'v.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        return $this->db->get()->row();
    }

    /**
     * Get species count
     *
     * @access   public
     * @param    $request_data
     * @return   Object
     */
    public function get_species_count($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $this->db->select('IFNULL(sum(cls.catch_qty),0) as total_catch_qty');
        $this->db->from('core_catch_logsheet_species cls');
        $this->db->join('core_catch_logsheets cl', 'cl.id = cls.catch_logsheet_id');
        $this->db->where(array('cl.entry_by' => $user_id, 'cls.deleted_by' => 0, 'cl.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        return $this->db->get()->row();
    }

    /**
     * Get exporter count
     *
     * @access   public
     * @param    $request_data
     * @return   Object
     */
    public function get_exporter_count($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $this->db->select('pl.exporter_id');
        $this->db->from("core_purchase_logsheets AS pl");
        $this->db->join("core_catch_logsheets cl", "cl.id = pl.catch_logsheet_id");
        $this->db->where(array('cl.entry_by' => $user_id, 'cl.deleted_by' => 0, 'pl.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        $this->db->group_by("pl.exporter_id");
        return $this->db->get()->num_rows();
    }

    /**
     * Get species count
     *
     * @access   public
     * @param    $request_data
     * @return   Object
     */
    public function get_species_inventory($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $limit = API_PAGE_LIMIT;
        $offset = ($request_data['page_no'] - 1) * $limit;
        $offset = ($offset > 0) ? $offset : 0;

        $this->db->select('cl.id, SUM(cls.catch_qty) as catch_qty, cls.spi_com_name, cls.fish_specie_id');
        $this->db->from('core_catch_logsheet_species cls');
        $this->db->join('core_catch_logsheets cl', 'cl.id = cls.catch_logsheet_id');

        $this->db->where(array('cl.entry_by' => $user_id, 'cls.deleted_by' => 0, 'cl.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        $this->db->group_by("cls.fish_specie_id");
        $qry_catch_log_species = $this->db->get_compiled_select();

        
        $this->db->select('cl.id,SUM(pls.purchase_qty) as sold_qty, pls.spi_com_name, pls.fish_specie_id');
        $this->db->from('core_purchase_logsheet_species pls');
        $this->db->join('core_purchase_logsheets pl', 'pl.id = pls.purchase_logsheet_id');
        $this->db->join('core_catch_logsheets cl', 'cl.id = pl.catch_logsheet_id AND cl.deleted_by = 0');

        $this->db->where(array('cl.entry_by' => $user_id, 'pls.deleted_by' => 0, 'cl.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        $this->db->group_by("pls.fish_specie_id");
        $qry_purchase_log_species = $this->db->get_compiled_select();

        //return  $this->db->query("SELECT a.catch_qty catch_qty,b.sold_qty sold_qty,(a.catch_qty - b.sold_qty) AS balance_qty, a.spi_com_name spi_com_name FROM ($qry_catch_log_species) as a LEFT JOIN ($qry_purchase_log_species) AS b on  a.fish_specie_id = b.fish_specie_id GROUP BY a.fish_specie_id")->result_array();

        $this->db->select("a.catch_qty catch_qty,b.sold_qty sold_qty,(a.catch_qty - b.sold_qty) AS balance_qty, a.spi_com_name spi_com_name");
        $this->db->from("(".$qry_catch_log_species.") as a");
        $this->db->join("(".$qry_purchase_log_species.") as b", 'a.fish_specie_id = b.fish_specie_id','left');
        $this->db->group_by("a.fish_specie_id");
        $query = $this->db->get_compiled_select();

        $this->db->from("(" . $query . ") as r1");
        if ($request_data['page_no'] != -1) {
            $this->db->limit($limit, $offset);
        }

        $returnArr['species_list'] =  $this->db->get()->result();

        if ($request_data['page_no'] == -1) {
            $returnArr['next_page_no'] = 0;
        } else {
            // Check is data has for next page
            $this->db->select('spi_com_name');
            $this->db->from("(" . $query . ") as r2");
            $offset = ($request_data['page_no']) * $limit;
            $offset = ($offset > 0) ? $offset : 0;
            $this->db->limit($limit, $offset);
            $next_result = $this->db->get()->result_array();
            $returnArr['next_page_no'] = (count($next_result) > 0) ? $request_data['page_no'] + 1 : 0;
        }
        return $returnArr;
    }

    /**
     * Get exporter list
     *
     * @access   public
     * @param    $request_data
     * @return   Object
     */
    public function get_exporter_list($request_data) {

        $user_id = $request_data['user_id'];
        $from_date = $request_data['from_date'];
        $to_date = $request_data['to_date'];

        $limit = API_PAGE_LIMIT;
        $offset = ($request_data['page_no'] - 1) * $limit;
        $offset = ($offset > 0) ? $offset : 0;

        $this->db->select("pl.exporter_id, CONCAT(u.first_name,' ',u.last_name) as exporter_name, ed.organization_name");
        $this->db->from("core_purchase_logsheets AS pl");
        $this->db->join("core_catch_logsheets cl", "cl.id = pl.catch_logsheet_id");
        $this->db->join("core_users u", "u.id = pl.exporter_id");
        $this->db->join("core_exporter_details ed", "ed.user_id = u.id");
        $this->db->where(array('cl.entry_by' => $user_id, 'cl.deleted_by' => 0, 'pl.deleted_by' => 0));

        $this->db->group_start();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_startdate >= ', $from_date);
        $this->db->where('cl.voyage_startdate <= ', $to_date);
        $this->db->group_end();

        $this->db->or_group_start();
        $this->db->where('cl.voyage_returndate >= ', $from_date);
        $this->db->where('cl.voyage_returndate <= ', $to_date);
        $this->db->group_end();

        $this->db->group_end();

        $this->db->group_by("pl.exporter_id");

        $query = $this->db->get_compiled_select();

        $this->db->from("(" . $query . ") as r1");
        if ($request_data['page_no'] != -1) {
            $this->db->limit($limit, $offset);
        }

        $returnArr['exporter_list'] =  $this->db->get()->result();

        if ($request_data['page_no'] == -1) {
            $returnArr['next_page_no'] = 0;
        } else {
            // Check is data has for next page
            $this->db->select('exporter_id');
            $this->db->from("(" . $query . ") as r2");
            $offset = ($request_data['page_no']) * $limit;
            $offset = ($offset > 0) ? $offset : 0;
            $this->db->limit($limit, $offset);
            $next_result = $this->db->get()->result_array();
            $returnArr['next_page_no'] = (count($next_result) > 0) ? $request_data['page_no'] + 1 : 0;
        }
        return $returnArr;

    }
}
