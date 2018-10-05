<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class MY_API_Model extends CI_Model {

    var $logged_id = 0;

    public function __construct() {

        parent::__construct();
        //MY_helper call helper method        
    }

    /**
     * list_data : get data of table based on different parameter
     * 
     * @access	public
     * @param	table_name, select, where, orderby, retrun_array(boolean)
     * @return	NA or array or object
     */
    public function list_data($table_name = "", $select = "*", $where = "", $orderby = "", $return_array = false) {

        if (!empty($table_name)) {
            $this->db->select($select);
            $this->db->from($table_name);
            if (is_array($where) && !empty($where)) {
                $this->db->where($where);
            } elseif ($where != "") {
                $this->db->where($where);
            }

            if (is_array($orderby) && !empty($orderby)) {
                $this->db->order_by(implode(",", $orderby));
            } elseif ($orderby != "") {
                $this->db->order_by($orderby);
            }

            $data = $this->db->get();
            if ($return_array) {
                return $list = $data->result_array();
            } else {
                return $list = $data->result();
            }
        }
        return "";
    }

    /**
     * single_data : get singal data of table based on different parameter
     * 
     * @access	public
     * @param	table_name, select, where, orderby, retrun_array(boolean)
     * @return	NA or array or object
     */
    public function single_data($table_name = "", $select = "*", $where = "", $orderby = "", $return_array = true) {

        if (!empty($table_name)) {
            $this->db->select($select)->from($table_name);
            (!empty($where)) ? $this->db->where($where) : "";
            (!empty($orderby)) ? $this->db->order_by($orderby) : "";
            $this->db->limit(1);
            $data = $this->db->get();
            if ($return_array) {
                return $data->row_array();
            } else {
                return $data->row();
            }
        }
        return array();
    }

    /**
     * insert_data : Insert into particular table
     * 
     * @access	public
     * @param	table_name, data array, batch boolean
     * @return	NA or last inserted id
     */
    public function insert_data($table_name = "", $data = array(), $batch = FALSE) {

        if (!empty($table_name) && !empty($data)) {
            if ($batch) {
                $last_id = 0;
                if (count($data) > 499) {
                    $_datas = array_chunk($data, 499);
                    foreach ($_datas as $batchPart) {
                        $this->db->insert_batch($table_name, $batchPart, NULL, 500);
                        $last_id = $last_id + $this->db->affected_rows();
                    }
                } else {
                    $this->db->insert_batch($table_name, $data, NULL, 500);
                    $last_id = $this->db->affected_rows();
                }
            } else {

                $query = $this->db->insert($table_name, $data);
                $last_id = $this->db->insert_id();
            }
            return $last_id;
        }
        return "";
    }

    /**
     * update_data : Update into particular table
     * 
     * @access	public
     * @param	table_name, data array, where array, is_delete, batch boolean
     * @return	NA or affected rows
     */
    public function update_data($table_name = "", $data = array(), $where = "", $is_delete = "", $batch = FALSE) {

        if (!empty($table_name) && !empty($data)) {

            if ($batch) {
                if (count($data) > 499) {
                    $_datas = array_chunk($data, 499);
                    foreach ($_datas as $batchPart) {
                        $success = $this->db->update_batch($table_name, $batchPart, $where, 500);
                    }
                } else {
                    $success = $this->db->update_batch($table_name, $data, $where, 500);
                }
            } else {
                if (is_array($where) && !empty($where)) {
                    $this->db->where($where);
                } elseif ($where != "") {
                    $this->db->where($where);
                }
                $success = $this->db->update($table_name, $data);
            }
            //return $this->db->affected_rows();
            return $success;
        }
        return $success;
    }

    /**
     * delete_data : Delete into particular table
     * 
     * @access	public
     * @param	table_name, data array, where array
     * @return	NA or affected rows
     */
    public function delete_data($table_name = "", $where = "") {

        if (!empty($table_name)) {
            (!empty($where)) ? $this->db->where($where) : "";
            $this->db->delete($table_name);
            return $this->db->affected_rows();
        }
        return "";
    }

    /**
     * Get part of order query
     *
     * @access private
     * @param order columns, post order, default order
     * @return Query Object
     */
    public function _order_query($column_order = array(), $post_order = array(), $colorder = "", $default_order = array(), $escape = false) {
        if (!empty($post_order)) { // here order processing
            $postedorder = $post_order["0"]["column"];
            $colorder = array_filter(explode(",", $colorder));
            $postedorder = (!empty($colorder)) ? $colorder[$postedorder] : $postedorder;
            $this->db->order_by($column_order[$postedorder], $post_order["0"]["dir"], $escape);
        } else if (!empty($default_order)) {
            if (is_array($default_order)) {
                $this->db->order_by(key($default_order), $default_order[key($default_order)]);
            } else {
                $this->db->order_by($default_order);
            }
        }
    }

    /**
     * Get part of search query
     *
     * @access private
     * @param search columns, search string
     * @return Query Object
     */
    public function _search_query($column_search = array(), $search_string = "", $side = "both", $escape = true) {
        $column_search = array_filter($column_search);
        $start = 0;
        foreach ($column_search as $i => $item) { // loop column
            $item = explode("|", $item);
            foreach ($item as $j => $v) {
                if ($start === 0 && $j == 0) { // first loop
                    $this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
                    $this->db->like($v, $search_string, $side, $escape);
                } else {
                    $this->db->or_like($v, $search_string, $side, $escape);
                }
            }

            if (count($column_search) - 1 == $start) //last loop
                $this->db->group_end(); //close bracket
            $start++;
        }
    }

    /**
     * Get part of search query
     *
     * @access private
     * @param search columns, search string
     * @return Query Object
     */
    public function _individual_search_query($column_search = array(), $side = "both", $escape = true) {
        $post_columns = $this->input->post("columns");
        foreach ($column_search as $i => $item) { // loop column
            if (isset($post_columns[$i]) && $post_columns[$i]["searchable"] == true && $post_columns[$i]["search"]["value"] != "") {
                $item = explode("|", $item);
                if (count($item) > 1) {
                    $this->db->group_start();
                    foreach ($item as $v) {
                        $this->db->or_like($v, $post_columns[$i]["search"]["value"], $side, $escape);
                    }
                    $this->db->group_end();
                } else {
                    $this->db->like($item[0], $post_columns[$i]["search"]["value"], $side, $escape);
                }
            }
        }
        if (!empty($this->input->post('search')['value'])) {
            $this->_search_query($column_search, $this->input->post('search')['value'], $side, $escape);
        }
    }

    /**
     * Get part of having query
     *
     * @access private
     * @param having columns, search string
     * @return Query Object
     */
    public function _having_query($column_having = array(), $search_string = "") {
        $post_columns = $this->input->post("columns");
        $andhaving = "( 1 = 1 ";
        foreach ($column_having as $i => $item) { // loop column
            if (isset($post_columns[$i]) && $post_columns[$i]["searchable"] == true && $post_columns[$i]["search"]["value"] != "") {
                $andhaving .= " AND " . $item . " like '%" . $this->db->escape_like_str($post_columns[$i]["search"]["value"]) . "%'";
            }
        }
        $andhaving .= ")";
        $orhaving = "";
        if (!empty($search_string)) {
            foreach ($column_having as $i => $item) { // loop column
                if (!empty($item)) {
                    $orhaving .= empty($orhaving) ? "(" : " OR ";
                    $orhaving .= $item . " like '%" . $this->db->escape_like_str($search_string) . "%'";
                }
            }
            $orhaving .= !empty($orhaving) ? ")" : "";
        }
        $havinglike = !empty($orhaving) ? $andhaving . " AND " . $orhaving : $andhaving;
        $this->db->having($havinglike);
    }

    /**
     * list_data_where_in : get data of table based on different parameter
     *
     * @access	public
     * @param	table_name, select, where, orderby, retrun_array(boolean)
     * @return	NA or array or object
     */
    public function list_data_where_in($table_name = "", $select = "*", $where_field, $ids, $orderby = "", $return_array = false) {

        if (!empty($table_name)) {
            $this->db->select($select);
            $this->db->from($table_name);
            $this->db->where_in($where_field, $ids);

            if (is_array($orderby) && !empty($orderby)) {
                $this->db->order_by(implode(",", $orderby));
            } elseif ($orderby != "") {
                $this->db->order_by($orderby);
            }

            $data = $this->db->get();
            if ($return_array) {
                return $list = $data->result_array();
            } else {
                return $list = $data->result();
            }
        }
        return "";
    }


}
