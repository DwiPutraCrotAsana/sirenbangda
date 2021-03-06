<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_renja_trx extends CI_Model
{
	var $table = 't_renja';
	var $table_misi = 't_renja_misi';
	var $table_tujuan = 't_renja_tujuan';
	var $table_sasaran = 't_renja_sasaran';
	var $table_program_kegiatan = 't_renja_prog_keg';
	var $table_indikator_program = 't_renja_indikator_prog_keg';
	var $master_urusan = 'm_urusan';
	var $master_bidang = 'm_bidang';
	var $master_program = 'm_program';
	var $master_kegiatan = 'm_kegiatan';

	var $is_program = 1;
	var $is_kegiatan = 2;

	var $id_status_baru = "1";
	var $id_status_send = "2";
	var $id_status_revisi = "3";
	var $id_status_approved = "4";
	var $id_status_baru2 = "5";
	var $id_status_revisi2 = "6";
	var $id_status_approved2 = "7";
	var $id_status_revisi_rpjm = "8";

	//Belum Di pakek
	//Karena belum ada buat history, nnti buatin history yaww
	var $historynya = 't_history_renja';
	var $baru = 'Renja Baru';
	var $edit = 'Renja Diubah';
	var $send = 'Pengajuan untuk Diverifikasi';
	var $revisi = 'Tidak Disetujui / Perlu Direvisi';
	var $approved = 'Telah Diverifikasi';
	var $delete_from_sended_list = 'Dihapus Dari Paket Pengiriman (Pernah Dikirim).';

	var $primary_renja = 'id';

    public function __construct()
    {
        parent::__construct();
    }

	##
	## New Generation
	##

    //---------------------------BARU BUAT-------------------------
    function get_nama_program($kd_urusan,$kd_bidang,$kd_program)
    {
    	$sql="
			SELECT Ket_Program AS ket_program FROM ".$this->master_program."
			WHERE Kd_Urusan = ".$kd_urusan." AND
					Kd_Bidang = ".$kd_bidang." AND
					Kd_Prog = ".$kd_program."
		";

		$query = $this->db->query($sql, array($kd_urusan));

		if($query) {
				if($query->num_rows() > 0) {
					return $query->row();
				}
			}

			return NULL;
    }

    function get_nama_kegiatan($kd_urusan,$kd_bidang,$kd_program,$kd_kegiatan)
    {
    	$sql="
			SELECT * FROM ".$this->master_kegiatan."
			WHERE Kd_Urusan = ? AND
					Kd_Bidang = ? AND
					Kd_Prog = ? AND
					Kd_Keg = ?
		";

		$query = $this->db->query($sql, array($kd_urusan,$kd_bidang,$kd_program,$kd_kegiatan));
		if($query) {
				if($query->num_rows() > 0) {

					return $query->row();
				}
			}

			return NULL;
    }

    function get_all_renja_prokeg($id_skpd,$no_result=FALSE)
    {
    	$this->db->from($this->table_program_kegiatan);
    	$this->db->where('id_skpd',$id_skpd);
		$result = $this->db->get();
		if ($no_result) {
			return $result;
		}else{
			return $result->result();
		}
    }

    function get_renja_detail($id)
    {
    	$sql="
			SELECT * FROM ".$this->table_program_kegiatan."
			WHERE id = ?
		";

		$query = $this->db->query($sql, array($id));

		if($query) {
				if($query->num_rows() > 0) {
					return $query->row();
				}
			}

			return NULL;
    }
	function get_renja($id_skpd,$ta)
    {
    	$sql="
			SELECT * FROM ".$this->table."
			WHERE id_skpd = ?
			AND tahun = ?
		";

		$query = $this->db->query($sql, array($id_skpd,$ta));

		if($query) {
				if($query->num_rows() > 0) {
					return $query->row();
				}
			}

			return NULL;
    }

	function get_renja_skpd($id_skpd,$ta)
    {
    	$sql="
			SELECT * FROM ".$this->table_program_kegiatan."
			WHERE id_skpd = ?
			AND tahun = ?
		";

		$query = $this->db->query($sql, array($id_skpd,$ta));

		if($query) {
				if($query->num_rows() > 0) {
					return $query->result();
				}
			}

			return NULL;
    }

    function simpan_renja($data_renja)
	{
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		//$data_cik->created_date		= Formatting::get_datetime();
		//$data_cik->created_by		= $this->session->userdata('username');

		$this->db->set($data_renja);
    	$this->db->insert('t_renja_prog_keg');

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

    function update_renja($data,$id,$table,$primary) {
        $this->db->where($this->$primary,$id);
        return $this->db->update($this->$table,$data);
    }
    //------------------------------SAMPAI SINI--------------------------

	private function create_history($data){
		$data['created_date'] = date("Y-m-d H:i:s");
		$data['created_by'] = $this->session->userdata('username');
		return $data;
	}

	private function change_history($data){
		$data['changed_date'] = date("Y-m-d H:i:s");
		$data['changed_by'] = $this->session->userdata('username');
		return $data;
	}

	private function add_misi($misi){
		$this->db->insert($this->table_misi, $misi);
		return $this->db->insert_id();
	}

	private function add_tujuan($tujuan){
		$this->db->insert_batch($this->table_tujuan, $tujuan);
	}

	private function update_tujuan($tujuan, $id){
		$this->db->where("id", $id);
		$this->db->update($this->table_tujuan, $tujuan);
	}

	private function update_status_after_edit($id, $id_program=NULL, $id_kegiatan=NULL){
		/*$query = "SELECT SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<=?), 1, 0)) as proses1, SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<=?), 1, 0)) as proses2 FROM t_renja_prog_keg INNER JOIN t_renja ON t_renja_prog_keg.id_renja=t_renja.id WHERE t_renja.id=? AND is_prog_or_keg=?";
		$data = array($this->id_status_send, $this->id_status_approved, $this->id_status_baru2, $this->id_status_approved2, $id_renja, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		$proses = $result->row();*/
		$proses = $this->cek_proses($id);

		if (!empty($id_program)) {
			$this->db->where("vw1.id", $id_program);
		}elseif (!empty($id_kegiatan)) {
			$this->db->where($this->table_program_kegiatan.".id", $id_kegiatan);
		}elseif (!empty($id)) {
			$this->db->where($this->table.".id", $id);
		}

		if (!empty($proses->proses2)) {
			$return = $this->db->update($this->table_program_kegiatan." INNER JOIN ". $this->table ." ON ". $this->table_program_kegiatan .".id_renja=". $this->table .".id"." INNER JOIN ". $this->table_program_kegiatan ." AS vw1 ON (". $this->table_program_kegiatan .".parent=vw1.id AND vw1.is_prog_or_keg=". $this->is_program .")", array($this->table_program_kegiatan.'.id_status'=>$this->id_status_baru2));
		}else{
			$return = $this->db->update($this->table_program_kegiatan." INNER JOIN ". $this->table ." ON ". $this->table_program_kegiatan .".id_renja=". $this->table .".id"." INNER JOIN ". $this->table_program_kegiatan ." AS vw1 ON (". $this->table_program_kegiatan .".parent=vw1.id AND vw1.is_prog_or_keg=". $this->is_program .")", array($this->table_program_kegiatan.'.id_status'=>$this->id_status_baru));
		}
		return $return;
	}

	function cek_proses($id=NULL, $id_skpd=NULL){
		if (!empty($id) && !empty($id_skpd)) {
			$where = "AND t_renja_prog_keg.id='". $id ."' AND t_renja_prog_keg.id_skpd='". $id_skpd ."'";
		}elseif (!empty($id)) {
			$where = "AND t_renja_prog_keg.id='". $id ."'";
		}elseif (!empty($id_skpd)) {
			$where = "AND t_renja_prog_keg.id_skpd='". $id_skpd ."'";
		}

		$query = "SELECT SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<?), 1, 0)) as proses1, SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<=?), 1, 0)) as proses2 FROM t_renja_prog_keg WHERE is_prog_or_keg=? ".$where;
		$data = array($this->id_status_send, $this->id_status_approved, $this->id_status_approved, $this->id_status_approved2, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	function get_one_renja_skpd($id_skpd, $detail=FALSE){
		$this->db->select($this->table.".*");
		$this->db->from($this->table);
		$this->db->where($this->table.".id_skpd", $id_skpd);

		if ($detail) {
			$this->db->select("nama_skpd");
			$this->db->join("m_skpd","t_renja.id_skpd = m_skpd.id_skpd","inner");
		}

		$result = $this->db->get();
		return $result->row();
	}

	function get_all_renja_misi($id_renja, $no_result=FALSE){
		$this->db->from($this->table_misi);
		$this->db->where("id_renja", $id_renja);
		$result = $this->db->get();
		if ($no_result) {
			return $result;
		}else{
			return $result->result();
		}
	}

	function get_all_renja_tujuan($id_renja, $no_result=FALSE){
		$this->db->from($this->table_tujuan);
		$this->db->where("id_renja", $id_renja);
		$result = $this->db->get();
		if ($no_result) {
			return $result;
		}else{
			return $result->result();
		}
	}

	function get_each_renja_tujuan($id_renja, $id_misi){
		$this->db->from($this->table_tujuan);
		$this->db->where("id_renja", $id_renja);
		$this->db->where("id_misi", $id_misi);
		$result = $this->db->get();
		return $result;
	}

	function get_one_renja_tujuan($id_renja, $id_tujuan){
		$this->db->from($this->table_tujuan);
		$this->db->where("id_renja", $id_renja);
		$this->db->where("id", $id_tujuan);
		$result = $this->db->get();
		return $result->row();
	}

	function add_renja_skpd($data, $misi, $tujuan){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$data = $this->create_history($data);

		$this->db->insert($this->table, $data);
		$id_renja = $this->db->insert_id();

		$id_misi = array();
		foreach ($misi as $key => $value) {
			$id_misi[$key] = $this->add_misi(array('id_renja' => $id_renja, 'misi' => $value));
		}

		$tujuan_batch = array();
		foreach ($misi as $key => $value) {
			foreach ($tujuan[$key] as $key1 => $value1) {
				$tujuan_batch[] = array('id_renja' => $id_renja, 'id_misi' => $id_misi[$key], 'tujuan' => $value1);
			}
		}

		$this->add_tujuan($tujuan_batch);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function edit_renja_skpd($data, $misi, $tujuan, $id_tujuan, $id_renja){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$data = $this->change_history($data);

		$this->db->update($this->table, $data);

		$this->db->where('id_renja', $id_renja);
		$this->db->delete($this->table_misi);
		$id_misi = array();
		foreach ($misi as $key => $value) {
			$id_misi[$key] = $this->add_misi(array('id_renja' => $id_renja, 'misi' => $value));
		}

		//tujuan batch untuk yg baru
		$tujuan_batch = array();
		foreach ($misi as $key => $value) {
			foreach ($tujuan[$key] as $key1 => $value1) {
				if (!empty($id_tujuan[$key][$key1])) {
					//update tujuannya
					$this->update_tujuan(array('tujuan' => $value1, 'id_misi' => $id_misi[$key]), $id_tujuan[$key][$key1]);
					unset($id_tujuan[$key][$key1]);
				}else{
					$tujuan_batch[] = array('id_renja' => $id_renja, 'id_misi' => $id_misi[$key], 'tujuan' => $value1);
				}
			}
		}

		if (!empty($tujuan_batch)) {
			$this->add_tujuan($tujuan_batch);
		}


		$id_tujuan_batch = array();
		foreach ($misi as $key => $value) {
			foreach ($tujuan[$key] as $key1 => $value1) {
				$id_tujuan_batch[] = $value1;
			}
		}

		if (!empty($id_tujuan_batch)) {
			$this->db->where_in('id', $id_tujuan_batch);
			$this->db->delete($this->table_tujuan);
		}

		$this->update_status_after_edit($id_renja);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_all_sasaran($id_renja, $id_tujuan=NULL, $with_satuan=FALSE){
		$this->db->select($this->table_sasaran.".*");
		$this->db->where('id_renja', $id_renja);
		if (!empty($id_tujuan)) {
			$this->db->where('id_tujuan', $id_tujuan);
		}
		$this->db->from($this->table_sasaran);

		if ($with_satuan) {
			$this->db->select("m_lov.nama_value");
			$this->db->join("m_lov",$this->table_sasaran.".satuan = m_lov.kode_value AND kode_app='1'","inner");
		}

		$result = $this->db->get();
		return $result->result();
	}

	function get_one_sasaran($id_renja=NULL, $id_tujuan=NULL, $id_sasaran){
		if (!empty($id_renja)) {
			$this->db->where('id_renja', $id_renja);
		}

		if (!empty($id_tujuan)) {
			$this->db->where('id_tujuan', $id_tujuan);
		}

		$this->db->where('id', $id_sasaran);
		$this->db->from($this->table_sasaran);
		$result = $this->db->get();
		return $result->row();
	}

	function add_sasaran_skpd($data){
		$result = $this->db->insert($this->table_sasaran, $data);
		return $result;
	}

	function edit_sasaran_skpd($data, $id_sasaran){
		$this->db->where('id', $id_sasaran);
		$result = $this->db->update($this->table_sasaran, $data);

		$renja = $this->get_one_sasaran(NULL, NULL, $id_sasaran);
		$this->update_status_after_edit($renja->id_renja, $id_sasaran);

		return $result;
	}

	function delete_sasaran($id){
		$this->db->where('id', $id);
		$result = $this->db->delete($this->table_sasaran);
		return $result;
	}

	function get_all_program($id_skpd,$ta){
		if ($this->session->userdata("id_skpd") > 100) {
			$id_skpd = $this->session->userdata("id_skpd");
			// $query = "SELECT * FROM (`$this->table_program_kegiatan`)
			// WHERE `id_skpd` in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')
			// AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			// ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";

			$query = "SELECT * FROM (SELECT *, id AS id_a,
			(SELECT SUM(nominal) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom1,
			(SELECT SUM(nominal_thndpn) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom2
 			FROM (`$this->table_program_kegiatan`) WHERE `id_skpd` in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')
			AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			) AS tref
			WHERE (tref.nom1 > 0)
			ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";


			$result = $this->db->query($query);
		}else {
			$cek = $this->m_skpd->get_kode_unit($id_skpd);
			if ($cek == $id_skpd) {
				$query = "SELECT * FROM (SELECT *, id AS id_a,
				(SELECT SUM(nominal) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom1,
				(SELECT SUM(nominal_thndpn) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom2
				FROM ".$this->table_program_kegiatan." WHERE `id_skpd` IN 
				(SELECT id_skpd FROM m_skpd WHERE kode_unit = '".$id_skpd."')
				AND `tahun` = '".$ta."' 
				AND `is_prog_or_keg` = ".$this->is_program." 
				) AS tref
				WHERE (tref.nom1 > 0)
				ORDER BY `kd_urusan` ASC, `kd_bidang` ASC, `kd_program` ASC";

				$result = $this->db->query($query);
			}else{
				$query = "SELECT * FROM (SELECT *, id AS id_a,
				(SELECT SUM(nominal) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom1,
				(SELECT SUM(nominal_thndpn) FROM ".$this->table_program_kegiatan." WHERE parent = id_a) AS nom2
				FROM ".$this->table_program_kegiatan." WHERE `id_skpd` = '".$id_skpd."' AND `tahun` = '".$ta."' 
				AND `is_prog_or_keg` = ".$this->is_program." 
				) AS tref
				WHERE (tref.nom1 > 0)
				ORDER BY `kd_urusan` ASC, `kd_bidang` ASC, `kd_program` ASC";

				$result = $this->db->query($query);
			}
			// $query = "SELECT * FROM (`$this->table_program_kegiatan`)
			// WHERE `id_skpd` = '$id_skpd'
			// AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			// ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";

			
			
			// if ($cek == $id_skpd) {
			// 	$query = "SELECT * FROM (`$this->table_program_kegiatan`)
			// 	WHERE `id_skpd` in (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')
			// 	AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			// 	ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";
			//
			// 	$result = $this->db->query($query);
			// }else {
			// 	$this->db->select($this->table_program_kegiatan.".*");
			// 	$this->db->where('id_skpd', $id_skpd);
			// 	$this->db->where('tahun', $ta);
			// 	$this->db->where('is_prog_or_keg', $this->is_program);
			// 	$this->db->from($this->table_program_kegiatan);
			// 	$this->db->order_by('kd_urusan', 'asc');
			// 	$this->db->order_by('kd_bidang', 'asc');
			// 	$this->db->order_by('kd_program', 'asc');
			//
			// 	$result = $this->db->get();
			// }
		}
		return $result->result();
	}

	function get_one_program($id=NULL, $detail=FALSE){
		if (!empty($id)) {
			$this->db->where($this->table_program_kegiatan.'.id', $id);
		}

		if ($detail) {
			$this->db->select($this->table_program_kegiatan.".*");
			$this->db->select("nama_skpd");

			$this->db->join($this->table, $this->table_program_kegiatan.".id = ".$this->table.".id","inner");
			$this->db->join("m_skpd", $this->table.".id_skpd = m_skpd.id_skpd","inner");

			$this->db->select("m_urusan.Nm_Urusan");
			$this->db->select("m_bidang.Nm_Bidang");
			$this->db->select("m_program.Ket_Program");
			$this->db->join("m_urusan",$this->table_program_kegiatan.".kd_urusan = m_urusan.Kd_Urusan","inner");
			$this->db->join("m_bidang",$this->table_program_kegiatan.".kd_urusan = m_bidang.Kd_Urusan AND ".$this->table_program_kegiatan.".kd_bidang = m_bidang.Kd_Bidang","inner");
			$this->db->join("m_program",$this->table_program_kegiatan.".kd_urusan = m_program.Kd_Urusan AND ".$this->table_program_kegiatan.".kd_bidang = m_program.Kd_Bidang AND ".$this->table_program_kegiatan.".kd_program = m_program.Kd_Prog","inner");
		}

		$this->db->where($this->table_program_kegiatan.'.id', $id);
		$this->db->from($this->table_program_kegiatan);
		$result = $this->db->get();
		return $result->row();
	}

	function get_info_kodefikasi_program($id_program=NULL){
		if (!empty($id_program)) {
			$this->db->select($this->table_program_kegiatan.".kd_urusan");
			$this->db->select($this->table_program_kegiatan.".kd_bidang");
			$this->db->select($this->table_program_kegiatan.".kd_program");
			$this->db->select($this->table_program_kegiatan.".nama_prog_or_keg");
			$this->db->from($this->table_program_kegiatan);
			$this->db->where($this->table_program_kegiatan.'.id', $id_program);
		}

		$result = $this->db->get();
		return $result->row();
	}

	function get_info_tujuan_n_sasaran_n_program($id){
		if (!empty($id)) {
			$this->db->select($this->table_program_kegiatan.".kd_urusan");
			$this->db->select($this->table_program_kegiatan.".kd_bidang");
			$this->db->select($this->table_program_kegiatan.".kd_program");
			$this->db->select($this->table_program_kegiatan.".nama_prog_or_keg");
			$this->db->from($this->table_program_kegiatan);
			$this->db->where($this->table_program_kegiatan.'.id', $id);
		}
		$result = $this->db->get();
		return $result->row();
	}

	function add_program_skpd($data, $indikator, $satuan_target, $status_indikator, $kategori_indikator, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$add = array('is_prog_or_keg'=> $this->is_program);
		$data = $this->global_function->add_array($data, $add);

		$this->db->insert($this->table_program_kegiatan, $data);

		$id = $this->db->insert_id();
		foreach ($indikator as $key => $value) {
			$this->db->insert($this->table_indikator_program, array('id_prog_keg' => $id, 'indikator' => $value, 'satuan_target' => $satuan_target[$key],
			'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key], 'target' => $target[$key],'target_thndpn' => $target_thndpn[$key]));
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function edit_program_skpd($data, $id_program, $indikator, $id_indikator_program, $satuan_target, $status_indikator, $kategori_indikator, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$add = array('is_prog_or_keg'=> $this->is_program);
		$data = $this->global_function->add_array($data, $add);

		$this->db->where('id', $id_program);
		$result = $this->db->update($this->table_program_kegiatan, $data);

		foreach ($indikator as $key => $value) {
			if (!empty($id_indikator_program[$key])) {
				$this->db->where('id', $id_indikator_program[$key]);
				$this->db->where('id_prog_keg', $id_program);
				$this->db->update($this->table_indikator_program, array('indikator' => $value, 'satuan_target' => $satuan_target[$key],
				'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
				unset($id_indikator_program[$key]);
			}else{
				$this->db->insert($this->table_indikator_program, array('id_prog_keg' => $id_program, 'indikator' => $value, 'satuan_target' => $satuan_target[$key],
				'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key], ));
			}
		}

		if (!empty($id_indikator_program)) {
			$this->db->where_in('id', $id_indikator_program);
			$this->db->delete($this->table_indikator_program);
		}

		$renja = $this->get_one_program($id_program);
		if ($renja->id_status == '3') {
			$this->update_status($renja->id,'1');
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function delete_program($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->where('is_prog_or_keg', $this->is_program);
		$this->db->delete($this->table_program_kegiatan);

		$this->db->where('parent', $id);
		$this->db->where('is_prog_or_keg', $this->is_kegiatan);
		$this->db->delete($this->table_program_kegiatan);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_all_kegiatan($id, $id_skpd, $ta){
		if ($this->session->userdata("id_skpd") > 100) {
			$id_skpd = $this->session->userdata("id_skpd");
			$query = "SELECT * FROM (`$this->table_program_kegiatan`)
			WHERE `id_skpd` in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')
			AND `tahun` = '$ta' AND parent = $id
			AND `is_prog_or_keg` = $this->is_kegiatan
			AND (nominal > 0)
			ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc, `kd_kegiatan` asc";

			$result = $this->db->query($query);
		}else {
			$cek = $this->m_skpd->get_kode_unit($id_skpd);
			if ($cek == $id_skpd) {
				$query = "SELECT * FROM (`$this->table_program_kegiatan`)
				WHERE `id_skpd` in (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')
				AND `tahun` = '$ta' AND parent = $id
				AND `is_prog_or_keg` = $this->is_kegiatan
				AND (nominal > 0)
				ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc, `kd_kegiatan` asc";

				$result = $this->db->query($query);
			}else {
				$this->db->select($this->table_program_kegiatan.".*");
				$this->db->where('id_skpd', $id_skpd);
				$this->db->where('tahun', $ta);
				$this->db->where('parent', $id);
				$this->db->where('is_prog_or_keg', $this->is_kegiatan);
				$this->db->where('nominal >', '0');
				$this->db->from($this->table_program_kegiatan);
				$this->db->order_by('kd_urusan','asc');
				$this->db->order_by('kd_bidang','asc');
				$this->db->order_by('kd_program','asc');
				$this->db->order_by('kd_kegiatan','asc');

				$result = $this->db->get();
			}
		}
		return $result->result();
	}

	function add_kegiatan_skpd($data, $indikator, $satuan_target, $status_indikator, $kategori_indikator, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		// $KodeUrusan = $data['kd_urusan'];
		// $KodeBidang = $data['kd_bidang'];
		// $kodeProgram = $data['kd_program'];
		// $KodeKegiatan = $data['kd_kegiatan'];
		// $thnskr = $dataKegiatan1['tahun'];
		// $thndpn = $dataKegiatan2['tahun'];
		// $created_date =  date("d-m-Y_H-i-s");

		$add = array('is_prog_or_keg'=> $this->is_kegiatan, 'id_status'=> $this->id_status_baru);
		$data = $this->global_function->add_array($data, $add);

		$this->db->insert($this->table_program_kegiatan, $data);

		$id = $this->db->insert_id();
		foreach ($indikator as $key => $value) {
			$this->db->insert($this->table_indikator_program, array('id_prog_keg' => $id, 'indikator' => $value, 'satuan_target' => $satuan_target[$key], 'status_indikator' => $status_indikator[$key],
			'kategori_indikator' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
		}

		// $banyakData1 = count($dataKegiatan1['kode_sumber_dana']);
		// for($i =1; $i <= $banyakData1; ++$i) {
		// 		$datatahun1_batch[] = array(
		// 			'tahun'=>$thnskr,
		// 			'kode_urusan'=>$KodeUrusan,
		// 			'kode_bidang' => $KodeBidang,
		// 			'kode_program' => $kodeProgram,
		// 			'id_keg' => $id,
		// 			'kode_kegiatan' => $KodeKegiatan,
		// 			'kode_sumber_dana' => $dataKegiatan1['kode_sumber_dana'][$i],
		// 			'kode_jenis_belanja' => $dataKegiatan1['kode_jenis_belanja'][$i],
		// 			'kode_kategori_belanja' => $dataKegiatan1['kode_kategori_belanja'][$i],
		// 			'kode_sub_kategori_belanja' => $dataKegiatan1['kode_sub_kategori_belanja'][$i],
		// 			'kode_belanja' => $dataKegiatan1['kode_belanja'][$i],
		// 			'uraian_belanja' => $dataKegiatan1['uraian_belanja'][$i],
		// 			'detil_uraian_belanja' => $dataKegiatan1['detil_uraian_belanja'][$i],
		// 			'volume' => $dataKegiatan1['volume'][$i],
		// 			'nominal_satuan' => $dataKegiatan1['nominal_satuan'][$i],
		// 			'satuan' => $dataKegiatan1['satuan'][$i],
		// 			'subtotal' => $dataKegiatan1['subtotal'][$i],
		// 			'is_tahun_sekarang'=>1,
		// 			'id_status_renja'=>1,
		// 			'created_date' => $created_date
		// 			)	;
		// }

		// $banyakData2 = count($dataKegiatan2['kode_sumber_dana']);
		// for($i =1; $i <= $banyakData2; ++$i) {
		// 		$datatahun2_batch[] = array(
		// 			'tahun'=>$thndpn,
		// 			'kode_urusan'=>$KodeUrusan,
		// 			'kode_bidang' => $KodeBidang,
		// 			'kode_program' => $kodeProgram,
		// 			'id_keg' => $id,
		// 			'kode_kegiatan' => $KodeKegiatan,
		// 			'kode_sumber_dana' => $dataKegiatan2['kode_sumber_dana'][$i],
		// 			'kode_jenis_belanja' => $dataKegiatan2['kode_jenis_belanja'][$i],
		// 			'kode_kategori_belanja' => $dataKegiatan2['kode_kategori_belanja'][$i],
		// 			'kode_sub_kategori_belanja' => $dataKegiatan2['kode_sub_kategori_belanja'][$i],
		// 			'kode_belanja' => $dataKegiatan2['kode_belanja'][$i],
		// 			'uraian_belanja' => $dataKegiatan2['uraian_belanja'][$i],
		// 			'detil_uraian_belanja' => $dataKegiatan2['detil_uraian_belanja'][$i],
		// 			'volume' => $dataKegiatan2['volume'][$i],
		// 			'nominal_satuan' => $dataKegiatan2['nominal_satuan'][$i],
		// 			'satuan' => $dataKegiatan2['satuan'][$i],
		// 			'subtotal' => $dataKegiatan2['subtotal'][$i],
		// 			'is_tahun_sekarang'=>0,
		// 			'id_status_renja'=>1,
		// 			'created_date' => $created_date
		// 			)	;
		// }

		// $this->db->insert_batch('t_renja_belanja_kegiatan', $datatahun1_batch);

		// $this->db->insert_batch('t_renja_belanja_kegiatan', $datatahun2_batch);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function edit_kegiatan_skpd($data, $id_kegiatan, $indikator, $id_indikator_kegiatan, $satuan_target, $status_indikator, $kategori_indikator, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$add = array('is_prog_or_keg'=> $this->is_kegiatan);
		$data = $this->global_function->add_array($data, $add);

		$this->db->where('id', $id_kegiatan);
		$result = $this->db->update($this->table_program_kegiatan, $data);


		foreach ($indikator as $key => $value) {
			if (!empty($id_indikator_kegiatan[$key])) {
				$this->db->where('id', $id_indikator_kegiatan[$key]);
				$this->db->where('id_prog_keg', $id_kegiatan);
				$this->db->update($this->table_indikator_program, array('indikator' => $value, 'satuan_target' => $satuan_target[$key], 'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],
				 'status_indikator_thndpn' => $status_indikator[$key], 'kategori_indikator_thndpn' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
				unset($id_indikator_kegiatan[$key]);
			}else{
				$this->db->insert($this->table_indikator_program, array('id_prog_keg' => $id_kegiatan, 'indikator' => $value, 'satuan_target' => $satuan_target[$key], 'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],
				 'status_indikator_thndpn' => $status_indikator[$key], 'kategori_indikator_thndpn' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
			}
		}

		if (!empty($id_indikator_kegiatan)) {
			$this->db->where_in('id', $id_indikator_kegiatan);
			$this->db->delete($this->table_indikator_program);
		}

		$renja = $this->get_one_kegiatan(NULL,$id_kegiatan);
		if ($renja->id_status == '3') {
			$this->update_status($renja->id,'1');
		} else if ($renja->id_status == '6') {
			$this->update_status($renja->id,'5');
		}

		// $KodeUrusan = $data['kd_urusan'];
		// $KodeBidang = $data['kd_bidang'];
		// $kodeProgram = $data['kd_program'];
		// $KodeKegiatan = $data['kd_kegiatan'];
		// $thnskr = $dataKegiatan1['tahun'];
		// $thndpn = $dataKegiatan2['tahun'];
		// $created_date =  date("d-m-Y_H-i-s");

		// $this->db->query("delete from t_renja_belanja_kegiatan where id_keg = $id_kegiatan ");

		// $banyakData1 = count($dataKegiatan1['kode_sumber_dana']);
		// for($i =1; $i <= $banyakData1; ++$i) {
		// 		$datatahun1_batch[] = array(
		// 			'tahun'=>$thnskr,
		// 			'kode_urusan'=>$KodeUrusan,
		// 			'kode_bidang' => $KodeBidang,
		// 			'kode_program' => $kodeProgram,
		// 			'id_keg' => $id_kegiatan,
		// 			'kode_kegiatan' => $KodeKegiatan,
		// 			'kode_sumber_dana' => $dataKegiatan1['kode_sumber_dana'][$i],
		// 			'kode_jenis_belanja' => $dataKegiatan1['kode_jenis_belanja'][$i],
		// 			'kode_kategori_belanja' => $dataKegiatan1['kode_kategori_belanja'][$i],
		// 			'kode_sub_kategori_belanja' => $dataKegiatan1['kode_sub_kategori_belanja'][$i],
		// 			'kode_belanja' => $dataKegiatan1['kode_belanja'][$i],
		// 			'uraian_belanja' => $dataKegiatan1['uraian_belanja'][$i],
		// 			'detil_uraian_belanja' => $dataKegiatan1['detil_uraian_belanja'][$i],
		// 			'volume' => $dataKegiatan1['volume'][$i],
		// 			'nominal_satuan' => $dataKegiatan1['nominal_satuan'][$i],
		// 			'satuan' => $dataKegiatan1['satuan'][$i],
		// 			'subtotal' => $dataKegiatan1['subtotal'][$i],
		// 			'is_tahun_sekarang'=>1,
		// 			'id_status_renja'=>1,
		// 			'created_date' => $created_date
		// 			)	;
		// }

		// $banyakData2 = count($dataKegiatan2['kode_sumber_dana']);
		// for($i =1; $i <= $banyakData2; ++$i) {
		// 		$datatahun2_batch[] = array(
		// 			'tahun'=>$thndpn,
		// 			'kode_urusan'=>$KodeUrusan,
		// 			'kode_bidang' => $KodeBidang,
		// 			'kode_program' => $kodeProgram,
		// 			'id_keg' => $id_kegiatan,
		// 			'kode_kegiatan' => $KodeKegiatan,
		// 			'kode_sumber_dana' => $dataKegiatan2['kode_sumber_dana'][$i],
		// 			'kode_jenis_belanja' => $dataKegiatan2['kode_jenis_belanja'][$i],
		// 			'kode_kategori_belanja' => $dataKegiatan2['kode_kategori_belanja'][$i],
		// 			'kode_sub_kategori_belanja' => $dataKegiatan2['kode_sub_kategori_belanja'][$i],
		// 			'kode_belanja' => $dataKegiatan2['kode_belanja'][$i],
		// 			'uraian_belanja' => $dataKegiatan2['uraian_belanja'][$i],
		// 			'detil_uraian_belanja' => $dataKegiatan2['detil_uraian_belanja'][$i],
		// 			'volume' => $dataKegiatan2['volume'][$i],
		// 			'nominal_satuan' => $dataKegiatan2['nominal_satuan'][$i],
		// 			'satuan' => $dataKegiatan2['satuan'][$i],
		// 			'subtotal' => $dataKegiatan2['subtotal'][$i],
		// 			'is_tahun_sekarang'=>0,
		// 			'id_status_renja'=>1,
		// 			'created_date' => $created_date
		// 			)	;
		// }

		// $this->db->insert_batch('t_renja_belanja_kegiatan', $datatahun1_batch);

		// $this->db->insert_batch('t_renja_belanja_kegiatan', $datatahun2_batch);


		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function update_status($id, $id_status)
     {
		$this->db->set('id_status',$id_status);
		$this->db->where('id', $id);
		$result=$this->db->update('t_renja_prog_keg');
		return $result;
	 }

	function delete_kegiatan($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->where('is_prog_or_keg', $this->is_kegiatan);
		$this->db->delete($this->table_program_kegiatan);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_one_kegiatan($id_program=NULL, $id, $detail=FALSE){
		if (!empty($id_program)) {
			$this->db->where('parent', $id_program);
		}

		if ($detail) {
			$this->db->select($this->table_program_kegiatan.".*");
			$this->db->select("nama_skpd");

			$this->db->join("m_skpd", $this->table_program_kegiatan.".id_skpd = m_skpd.id_skpd","inner");

			$this->db->select("m_urusan.Nm_Urusan");
			$this->db->select("m_bidang.Nm_Bidang");
			$this->db->select("m_program.Ket_Program");
			$this->db->join("m_urusan",$this->table_program_kegiatan.".kd_urusan = m_urusan.Kd_Urusan","inner");
			$this->db->join("m_bidang",$this->table_program_kegiatan.".kd_urusan = m_bidang.Kd_Urusan AND ".$this->table_program_kegiatan.".kd_bidang = m_bidang.Kd_Bidang","inner");
			$this->db->join("m_program",$this->table_program_kegiatan.".kd_urusan = m_program.Kd_Urusan AND ".$this->table_program_kegiatan.".kd_bidang = m_program.Kd_Bidang AND ".$this->table_program_kegiatan.".kd_program = m_program.Kd_Prog","inner");
		}

		$this->db->where($this->table_program_kegiatan.'.id', $id);
		$this->db->from($this->table_program_kegiatan);
		$result = $this->db->get();
		return $result->row();
	}


	function kirim_renja($id_skpd,$ta) {
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		$data_renja = $this->get_renja_skpd($id_skpd,$ta);
		//echo $this->db->last_query();
		foreach ($data_renja as $renja){
			if($renja->id_status=='1'){
				$this->update_status($renja->id,'2');
			}else if ($renja->id_status=='5'){
				$this->update_status($renja->id,'4');
			}
		}
		$this->db->trans_complete();
		return $this->db->trans_status();
	}
	/*function kirim_renja($id_skpd){
		$query = "SELECT SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<=?), 1, 0)) as proses1,
		                 SUM(IF((t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.id_status<=?), 1, 0)) as proses2
				  FROM t_renja_prog_keg
				  WHERE t_renja_prog_keg.id_skpd=? AND is_prog_or_keg=?";
		$data = array($this->id_status_send, $this->id_status_approved, $this->id_status_baru2, $this->id_status_approved2, $id_skpd, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		$proses = $result->row();

		$proses = $this->cek_proses(NULL, $id_skpd);

		if (!empty($proses->proses2)) {
			$id_status_data = $this->id_status_approved;
		}else{
			$id_status_data = $this->id_status_send;
		}

		$this->db->where($this->table_program_kegiatan.".id_skpd", $id_skpd);
		$this->db->where($this->table_program_kegiatan.".id_status !=", $this->id_status_approved);
		$return = $this->db->update($this->table_program_kegiatan,array($this->table_program_kegiatan.'.id_status'=>$id_status_data));
		return $return;
	}*/

	function count_jendela_kontrol($id_skpd,$ta){
		if($this->session->userdata("id_skpd") > 100){
			$id_skpd = $this->session->userdata("id_skpd");
			$search = "AND t_renja_prog_keg.id_skpd in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')";
		}else {
			$kode_unit = $this->m_skpd->get_kode_unit($id_skpd);
			if ($id_skpd == $kode_unit) {
				$search = "AND t_renja_prog_keg.id_skpd in (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')";
				$search2 = "id_skpd IN (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')";
			}else {
				$search = "AND (t_renja_prog_keg.id_skpd = '$id_skpd' OR t_renja_prog_keg.id_skpd = '$kode_unit')";
				$search2 = "id_skpd = '$id_skpd'";
			}
		}
		$query = "SELECT
						SUM(IF(t_renja_prog_keg.id_status=?, 1, 0)) as baru,
						SUM(IF(t_renja_prog_keg.id_status>=?, 1, 0)) as kirim,
						SUM(IF(t_renja_prog_keg.id_status>?, 1, 0)) as proses,
						SUM(IF(t_renja_prog_keg.id_status=?, 1, 0)) as revisi,
						SUM(IF(t_renja_prog_keg.id_status>=?, 1, 0)) as veri,
						SUM(IF(t_renja_prog_keg.id_status=?, 1, 0)) as baru2,
						SUM(IF(t_renja_prog_keg.id_status>=?, 1, 0)) as kirim2,
						SUM(IF(t_renja_prog_keg.id_status>?, 1, 0)) as proses2,
						SUM(IF(t_renja_prog_keg.id_status=?, 1, 0)) as revisi2,
						SUM(IF(t_renja_prog_keg.id_status>=?, 1, 0)) as veri2,
						SUM(IF(t_renja_prog_keg.id_status=?, 1, 0)) as revisi_rpjm
					FROM
						t_renja_prog_keg
					WHERE
						tahun = ?".$search ." AND
						((t_renja_prog_keg.is_prog_or_keg =2 and nominal >0) or
							id in (select parent from t_renja_prog_keg where ".$search2." and tahun ='$ta' and nominal>0))";
		$data = array(
					$this->id_status_baru,
					$this->id_status_send,
					$this->id_status_send,
					$this->id_status_revisi,
					$this->id_status_approved,
					$this->id_status_baru2,
					$this->id_status_approved,
					$this->id_status_baru2,
					$this->id_status_revisi2,
					$this->id_status_approved2,
					$this->id_status_revisi_rpjm,
					$ta);
		$result = $this->db->query($query, $data);
		// print_r($this->db->last_query());
		// print_r($kode_unit);
		// print_r($id_skpd);
		// exit();
		return $result->row();
	}

	function get_all_renja($search, $start, $length, $order, $id_skpd, $order_arr, $status = NULL, $detail = FALSE){
		$this->db->select($this->table_program_kegiatan.".*");
		$this->db->select("status_renja");
		$this->db->from($this->table_program_kegiatan);
		$this->db->join($this->table,$this->table.".id = ". $this->table_program_kegiatan .".id_renja","inner");
		$this->db->where($this->table_program_kegiatan.".is_prog_or_keg", $this->is_kegiatan);

		if ($id_skpd != "all") {
			$this->db->where($this->table.".id_skpd", $id_skpd);
		}
		if ($status=="BARU") {
			$this->db->where("id_status", $this->id_status_baru);
		}elseif ($status=="VERIFIKASI") {
			$this->db->where("id_status", $this->id_status_send);
		}elseif ($status=="APPROVED") {
			$this->db->where("id_status", $this->id_status_approved);
		}
		if (!is_null($search)) {
			$this->db->where("(CONCAT(kd_urusan,\".\",kd_bidang,\".\",kd_program,\".\",kd_kegiatan) LIKE '%". $search['value'] ."%' OR nama_prog_or_keg LIKE '%". $search['value'] ."%' OR indikator_kinerja LIKE '%". $search['value'] ."%' OR status_renja LIKE '%". $search['value'] ."%')");
		}

		if ($detail) {
			$this->db->select("m_bidkoordinasi.nama_koor");
			$this->db->select("m_skpd.nama_skpd");
			$this->db->join("m_skpd",$this->table.".id_skpd = m_skpd.id_skpd","inner");
			$this->db->join("m_bidkoordinasi","m_skpd.id_bidkoor = m_bidkoordinasi.id_bidkoor","inner");
		}

		if (!is_null($length) && !is_null($start)) {
			$this->db->limit($length, $start);
		}
		if (!is_null($order)) {
			$this->db->order_by($order_arr[$order["column"]], $order["dir"]);
		}

		$this->db->join("m_status_renja",$this->table_program_kegiatan.".id_status = m_status_renja.id","inner");
		$result = $this->db->get();
		return $result->result();
	}

	function count_all_renja($search, $id_skpd, $status = NULL){
		$this->db->from($this->table_program_kegiatan);
		$this->db->join($this->table,$this->table.".id = ". $this->table_program_kegiatan .".id_renja","inner");
		$this->db->where($this->table_program_kegiatan.".is_prog_or_keg", $this->is_kegiatan);

		if ($id_skpd != "all") {
			$this->db->where($this->table.".id_skpd", $id_skpd);
		}
		if ($status=="BARU") {
			$this->db->where("id_status", $this->id_status_baru);
		}elseif ($status=="VERIFIKASI") {
			$this->db->where("id_status", $this->id_status_send);
		}elseif ($status=="APPROVED") {
			$this->db->where("id_status", $this->id_status_approved);
		}
		if (!is_null($search)) {
			$this->db->where("(CONCAT(kd_urusan,\".\",kd_bidang,\".\",kd_program,\".\",kd_kegiatan) LIKE '%". $search['value'] ."%' OR nama_prog_or_keg LIKE '%". $search['value'] ."%' OR indikator_kinerja LIKE '%". $search['value'] ."%' OR status_renja LIKE '%". $search['value'] ."%')");
		}

		$this->db->join("m_status_renja",$this->table_program_kegiatan.".id_status = m_status_renja.id","inner");
		$result = $this->db->count_all_results();
		return $result;
	}

	function get_program_skpd_4_cetak($id_skpd,$tahun){
		//
		$query = "SELECT
						keg.penanggung_jawab, keg.lokasi, keg.lokasi_thndpn, keg.catatan,
						pro.*,
						SUM(keg.nominal) AS sum_nominal,
						SUM(keg.nominal_thndpn) AS sum_nominal_thndpn
					FROM
						(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=1 AND id_skpd > 0) AS pro
					INNER JOIN
						(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=2 AND id_skpd > 0) AS keg ON keg.parent=pro.id
					WHERE
						keg.id_skpd=?
						AND keg.tahun=?
					GROUP BY pro.id
					ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC, kd_kegiatan ASC";
		$data = array($id_skpd,$tahun);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_program_skpd_4_cetak_v2($id_skpd,$tahun,$kd_urusan,$kd_bidang, $for_where=NULL){
		if (empty($for_where)) {
			$for_where = "keg.id_skpd = ".$id_skpd."";
		}

		$query = "SELECT
						keg.penanggung_jawab, keg.lokasi, keg.lokasi_thndpn, keg.catatan,
						pro.*,
						SUM(keg.nominal) AS sum_nominal,
						SUM(keg.nominal_thndpn) AS sum_nominal_thndpn
					FROM
						(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=1) AS pro
					INNER JOIN
						(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=2 AND id IN (SELECT id_prog_keg 
FROM t_renja_indikator_prog_keg WHERE target > 0)) AS keg ON keg.parent=pro.id
					WHERE
						".$for_where."
						AND keg.tahun=?
						AND keg.kd_urusan = ?
						AND keg.kd_bidang = ?
						AND pro.id IN (SELECT id_prog_keg FROM t_renja_indikator_prog_keg
						WHERE id_prog_keg = pro.id
						AND (target > 0 OR target_thndpn > 0))
					GROUP BY pro.id
					HAVING SUM(keg.nominal) > 0
					ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC, kd_kegiatan ASC";
		$data = array($tahun,$kd_urusan,$kd_bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
	}


	function get_kegiatan_skpd_4_cetak($id_program){
		$query = "SELECT
						t_renja_prog_keg.*
					FROM t_renja_prog_keg
					WHERE parent=?
					AND (nominal > 0 OR nominal_thndpn > 0)
					AND id IN (SELECT id_prog_keg FROM t_renja_indikator_prog_keg
					WHERE id_prog_keg = t_renja_prog_keg.id AND target > 0)
					ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC, kd_kegiatan ASC";
		$data = array($id_program);
		$result = $this->db->query($query, $data);
		return $result;
	}

	function get_kegiatan_skpd_4_cetak_vdee($id_program){
		$query = "SELECT
						t_renja_prog_keg.*
					FROM t_renja_prog_keg
					WHERE parent=? and nominal > 0
					ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC, kd_kegiatan ASC";
		$data = array($id_program);
		$result = $this->db->query($query, $data);
		return $result;
	}

	function get_total_kegiatan_dan_indikator($id_program){
		$query = "SELECT
						COUNT(*) AS total
					FROM
						t_renja_prog_keg
					INNER JOIN
						t_renja_indikator_prog_keg ON t_renja_indikator_prog_keg.id_prog_keg=t_renja_prog_keg.id
					WHERE
						t_renja_prog_keg.parent=? OR t_renja_prog_keg.id=?";
		$data = array($id_program, $id_program);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	function get_revisi_awal($id){
		$query = "
					SELECT * FROM t_renja_revisi
					WHERE t_renja_revisi.`id_renja` = ?
					ORDER BY t_renja_revisi.`id` ASC
				 ";
		$data = array($id);
		$result = $this->db->query($query,$data);
		return $result->result();
	}

	function get_revisi_akhir($id){
		$query = "
					SELECT * FROM t_renja_revisi_keg
					WHERE t_renja_revisi_keg.`id_prog_keg` = ?
					ORDER BY t_renja_revisi_keg.`id` ASC
				 ";
		$data = array($id);
		$result = $this->db->query($query,$data);
		return $result->result();
	}

	function get_revisi_renja_ranwal($id_skpd,$ta){
		$query = "
					SELECT *
					FROM t_renja_revisi
					INNER JOIN t_renja_prog_keg ON t_renja_revisi.`id_renja`=t_renja_prog_keg.`id`
					WHERE t_renja_prog_keg.`id_skpd` = ?
					AND t_renja_prog_keg.`tahun` = ?
					AND t_renja_prog_keg.`id_status` = 3
					ORDER BY t_renja_prog_keg.`kd_urusan`,t_renja_prog_keg.`kd_bidang`,
					t_renja_prog_keg.`kd_program`,t_renja_prog_keg.`kd_kegiatan`
				 ";
		$data = array($id_skpd,$ta);
		$result = $this->db->query($query,$data);
		return $result->result();
	}

	function get_revisi_renja_akhir($id_skpd,$ta){
		$query = "
					SELECT  b.id, b.kd_urusan, b.kd_bidang, b.kd_program, b.kd_kegiatan, b.nominal, a.nominal as nom, a.ket_revisi,
					        b.id_skpd, b.id_status, b.tahun, b.nama_prog_or_keg
					FROM t_renja_revisi_keg a
					INNER JOIN t_renja_prog_keg b ON a.id_prog_keg = b.id
					WHERE b.id_skpd = ?
					AND b.tahun = ?
					AND b.id_status = 6
					ORDER BY b.kd_urusan,b.kd_bidang,
					b.kd_program,b.kd_kegiatan
		";
		$data = array($id_skpd,$ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_revisi_rpjm($id_renja){
		$query = "SELECT `t_renja_revisi_keg`.*, `t_renja_prog_keg`.`kd_urusan`, `t_renja_prog_keg`.`kd_bidang`, `t_renja_prog_keg`.`kd_program`, `t_renja_prog_keg`.`nama_prog_or_keg`, `t_renja_prog_keg`.`nominal_1_tot` AS nominal_1_sblm, `t_renja_prog_keg`.`nominal_2_tot` AS nominal_2_sblm, `t_renja_prog_keg`.`nominal_3_tot` AS nominal_3_sblm, `t_renja_prog_keg`.`nominal_4_tot` AS nominal_4_sblm, `t_renja_prog_keg`.`nominal_5_tot` AS nominal_5_sblm FROM (SELECT * FROM `t_renja_revisi_keg` WHERE id IN (SELECT max(id) FROM t_renja_revisi_keg GROUP BY id_prog_keg) GROUP BY id_prog_keg) AS `t_renja_revisi_keg` INNER JOIN (SELECT vw.*, SUM(t_renja_prog_keg.nominal_1) AS nominal_1_tot, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_tot, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_tot, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_tot, SUM(t_renja_prog_keg.nominal_5) AS nominal_5_tot FROM (SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=?) AS vw INNER JOIN t_renja_prog_keg ON vw.id=t_renja_prog_keg.parent AND t_renja_prog_keg.is_prog_or_keg=? GROUP BY vw.id) AS `t_renja_prog_keg` ON `t_renja_revisi_keg`.`id_prog_keg` = `t_renja_prog_keg`.`id` WHERE `id_renja` = ? AND is_revisi_rpjm='1' ORDER BY `t_renja_revisi_keg`.`id` desc";
		$data = array($this->is_program, $this->is_kegiatan, $id_renja);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_4_cetak_final($urusan, $bidang){
		$query = "SELECT t_renja_prog_keg.*, m_bidang.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro FROM t_renja_prog_keg INNER JOIN m_bidang ON (t_renja_prog_keg.kd_urusan=m_bidang.Kd_Urusan AND t_renja_prog_keg.kd_bidang=m_bidang.Kd_Bidang) WHERE t_renja_prog_keg.id_status=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang";
		$data = array($this->id_status_approved, $this->is_kegiatan, $urusan, $bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_skpd_4_cetak_final($urusan, $bidang){
		$query = "SELECT t_renja_prog_keg.*,m_skpd.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro FROM t_renja_prog_keg INNER JOIN t_renja ON (t_renja.id=t_renja_prog_keg.id_renja AND t_renja_prog_keg.is_prog_or_keg=?) INNER JOIN m_skpd ON t_renja.id_skpd=m_skpd.id_skpd WHERE t_renja_prog_keg.id_status=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, m_skpd.id_skpd";
		$data = array($this->is_kegiatan, $this->id_status_approved, $this->is_kegiatan, $urusan, $bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_skpd_program_4_cetak_final($urusan, $bidang, $skpd){
		$query = "SELECT vw1.*,m_lov.nama_value,m_skpd.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro FROM t_renja_prog_keg INNER JOIN t_renja ON (t_renja.id=t_renja_prog_keg.id_renja AND t_renja_prog_keg.is_prog_or_keg=?) INNER JOIN m_skpd ON t_renja.id_skpd=m_skpd.id_skpd INNER JOIN t_renja_prog_keg AS vw1 ON (vw1.id=t_renja_prog_keg.parent AND vw1.is_prog_or_keg=?) INNER JOIN m_lov ON vw1.satuan_target=m_lov.kode_value AND kode_app='1' WHERE t_renja_prog_keg.id_status=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? AND t_renja.id_skpd=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, m_skpd.id_skpd, t_renja_prog_keg.kd_program";
		$data = array($this->is_kegiatan, $this->is_program, $this->id_status_approved, $this->is_kegiatan, $urusan, $bidang, $skpd);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_program_veri_akhir($urusan, $bidang){
		$query = "SELECT * FROM t_renja_prog_keg INNER JOIN m_bidang ON (t_renja_prog_keg.kd_urusan=m_bidang.Kd_Urusan AND t_renja_prog_keg.kd_bidang=m_bidang.Kd_Bidang) INNER JOIN m_urusan ON t_renja_prog_keg.kd_urusan=m_urusan.Kd_Urusan WHERE t_renja_prog_keg.id_status=? AND t_renja_prog_keg.is_prog_or_keg=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang";
		$data = array($this->id_status_approved, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function revisi_rpjmd($id_program){
		$query = "SELECT t_renja_revisi_keg.*, t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, t_renja_prog_keg.kd_program, t_renja_prog_keg.nama_prog_or_keg FROM (SELECT * FROM `t_renja_revisi_keg` WHERE id IN (SELECT max(id) FROM t_renja_revisi_keg GROUP BY id_prog_keg) GROUP BY id_prog_keg) AS `t_renja_revisi_keg` INNER JOIN t_renja_prog_keg ON t_renja_revisi_keg.id_prog_keg=t_renja_prog_keg.id AND t_renja_prog_keg.is_prog_or_keg=? WHERE t_renja_prog_keg.id=?";
		$data = array($this->is_program, $id_program);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	function cek_nominal_banding_dengan_rpjmd($id, $urusan, $bidang, $program){
		$query = "SELECT SUM(nominal_1) AS nominal_1_pro, SUM(nominal_2) AS nominal_2_pro, SUM(nominal_3) AS nominal_3_pro, SUM(nominal_4) AS nominal_4_pro FROM t_renja_prog_keg WHERE is_prog_or_keg=? AND id!=? AND kd_urusan=? AND kd_bidang=? AND kd_program=? GROUP BY kd_urusan, kd_bidang, kd_program";
		$data = array($this->is_kegiatan, $id, $urusan, $bidang, $program);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	function get_total_nominal_renja($id_skpd=NULL){
		$this->db->select('COUNT(t_renja_prog_keg.id) AS count');
		$this->db->select_sum('nominal');
		$this->db->select_sum('nominal_thndpn');

		$proses = $this->count_jendela_kontrol($id_skpd);
		if (!empty($proses->veri2)) {
			$this->db->where("id_status", $this->id_status_approved2);
		}else{
			$this->db->where("id_status", $this->id_status_approved);
		}

		if (!is_null($id_skpd) && $id_skpd != "all") {
			$this->db->where("t_renja.id_skpd", $id_skpd);
		}

		$this->db->where("t_renja_prog_keg.is_prog_or_keg", $this->is_kegiatan);
		$this->db->from($this->table_program_kegiatan);
		$this->db->join($this->table,$this->table.".id = ". $this->table_program_kegiatan .".id_renstra","inner");

		$result = $this->db->get();
		return $result->row();
	}

	function get_all_skpd(){
		$query = "SELECT id_skpd FROM (SELECT * FROM t_renja_prog_keg GROUP BY id_renja) t_renja_prog_keg INNER JOIN t_renja ON t_renja.id=t_renja_prog_keg.id_renja";
		$result = $this->db->query($query);
		return $result->result();
	}

	function get_all_renja_revisi_rpjm(){
		$query = "SELECT COUNT(*) AS jml_data, m_urusan.*, m_bidang.* FROM t_renja_prog_keg INNER JOIN m_bidang ON (t_renja_prog_keg.kd_urusan=m_bidang.Kd_Urusan AND t_renja_prog_keg.kd_bidang=m_bidang.Kd_Bidang) INNER JOIN m_urusan ON t_renja_prog_keg.kd_urusan=m_urusan.Kd_Urusan WHERE t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.is_prog_or_keg=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang";
		$data = array($this->id_status_approved2, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_revisi_rpjm($urusan, $bidang){
		$query = "SELECT t_renja_prog_keg.*, m_bidang.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro, SUM(t_renja_prog_keg.nominal_5) AS nominal_5_pro FROM t_renja_prog_keg INNER JOIN m_bidang ON (t_renja_prog_keg.kd_urusan=m_bidang.Kd_Urusan AND t_renja_prog_keg.kd_bidang=m_bidang.Kd_Bidang) WHERE t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang";
		$data = array($this->id_status_approved2, $this->is_kegiatan, $urusan, $bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_skpd_revisi_rpjm($urusan, $bidang){
		$query = "SELECT t_renja_prog_keg.*,m_skpd.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro, SUM(t_renja_prog_keg.nominal_5) AS nominal_5_pro FROM t_renja_prog_keg INNER JOIN t_renja ON (t_renja.id=t_renja_prog_keg.id_renja AND t_renja_prog_keg.is_prog_or_keg=?) INNER JOIN m_skpd ON t_renja.id_skpd=m_skpd.id_skpd WHERE t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, m_skpd.id_skpd";
		$data = array($this->is_kegiatan, $this->id_status_approved2, $this->is_kegiatan, $urusan, $bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_bidang_urusan_skpd_program_revisi_rpjm($urusan, $bidang, $skpd){
		$query = "SELECT vw1.*,m_lov.nama_value,m_skpd.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro, SUM(t_renja_prog_keg.nominal_5) AS nominal_5_pro FROM t_renja_prog_keg INNER JOIN t_renja ON (t_renja.id=t_renja_prog_keg.id_renja AND t_renja_prog_keg.is_prog_or_keg=?) INNER JOIN m_skpd ON t_renja.id_skpd=m_skpd.id_skpd INNER JOIN t_renja_prog_keg AS vw1 ON (vw1.id=t_renja_prog_keg.parent AND vw1.is_prog_or_keg=?) INNER JOIN m_lov ON vw1.satuan_target=m_lov.kode_value AND kode_app='1' WHERE t_renja_prog_keg.id_status>=? AND t_renja_prog_keg.is_prog_or_keg=? AND t_renja_prog_keg.kd_urusan=? AND t_renja_prog_keg.kd_bidang=? AND t_renja.id_skpd=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, m_skpd.id_skpd, t_renja_prog_keg.kd_program";
		$data = array($this->is_kegiatan, $this->is_program, $this->id_status_approved2, $this->is_kegiatan, $urusan, $bidang, $skpd);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_one_bidang_urusan_skpd_program_revisi_rpjm($id_program){
		$query = "SELECT vw1.*,m_lov.nama_value,m_bidang.Nm_Bidang,m_skpd.*,SUM(t_renja_prog_keg.nominal_1) AS nominal_1_pro, SUM(t_renja_prog_keg.nominal_2) AS nominal_2_pro, SUM(t_renja_prog_keg.nominal_3) AS nominal_3_pro, SUM(t_renja_prog_keg.nominal_4) AS nominal_4_pro, SUM(t_renja_prog_keg.nominal_5) AS nominal_5_pro FROM t_renja_prog_keg INNER JOIN t_renja ON (t_renja.id=t_renja_prog_keg.id_renja AND t_renja_prog_keg.is_prog_or_keg=?) INNER JOIN m_skpd ON t_renja.id_skpd=m_skpd.id_skpd INNER JOIN t_renja_prog_keg AS vw1 ON (vw1.id=t_renja_prog_keg.parent AND vw1.is_prog_or_keg=?) INNER JOIN m_bidang ON m_bidang.Kd_Urusan=vw1.kd_urusan AND m_bidang.Kd_Bidang=vw1.kd_bidang INNER JOIN m_lov ON vw1.satuan_target=m_lov.kode_value AND kode_app='1' WHERE t_renja_prog_keg.id_status=? AND t_renja_prog_keg.is_prog_or_keg=? AND vw1.id=? GROUP BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, m_skpd.id_skpd, t_renja_prog_keg.kd_program";
		$data = array($this->is_kegiatan, $this->is_program, $this->id_status_approved2, $this->is_kegiatan, $id_program);
		$result = $this->db->query($query, $data);
		return $result->row();
	}
	function revisi_rpjm($id, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where($this->table_program_kegiatan.".parent", $id);
		$this->db->where($this->table_program_kegiatan.".is_prog_or_keg", $this->is_kegiatan);
		$return = $this->db->update($this->table_program_kegiatan, array($this->table_program_kegiatan.'.id_status'=>$this->id_status_revisi_rpjm));
		$result = $this->db->insert("t_renja_revisi_keg", $data);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}
	###############################################################################################################

	private function add_history($id_renja, $status, $keterangan=NULL){
		$history = array('id_renja' => $id_renja, 'status_renja' => $status, 'create_date'=>date("Y-m-d H:i:s"), 'user'=>$this->session->userdata('username'));
		if (!empty($keterangan)) {
			$history['keterangan'] = $keterangan;
		}
		$result = $this->db->insert($this->historynya, $history);
		return $result;
	}

	function add($data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$result = $this->db->insert($this->table, $data);
		$insert_id = $this->db->insert_id();

		$this->add_history($insert_id, $this->baru);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function edit($data, $id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$result = $this->db->update($this->table, $data);

		$this->add_history($id, $this->edit);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function delete($id, $id_skpd){
		$this->db->where('id', $id);
		$this->db->where("id_skpd", $id_skpd);
		$result = $this->db->delete($this->table);
		return $result;
	}

	function get_one_renja($id_renja, $id_skpd){
		$this->db->from($this->table);
		$this->db->where("id_skpd", $id_skpd);
		$this->db->where("id", $id_renja);
		$result = $this->db->get();
		return $result->row();
	}

	/*function get_one_renja_detail($id_renja, $status=NULL){
		$this->db->select("t_renja.*");
		$this->db->select("status_renja");
		$this->db->select("m_bidkoordinasi.nama_koor");
		$this->db->select("m_skpd.nama_skpd");

		$this->db->from($this->table);
		$this->db->where("t_renja.id", $id_renja);

		if ($status=="BARU") {
			$this->db->where("id_status", $this->id_status_baru);
		}elseif ($status=="VERIFIKASI") {
			$this->db->where("id_status", $this->id_status_send);
		}elseif ($status=="APPROVED") {
			$this->db->where("id_status", $this->id_status_approved);
		}

		$this->db->join("m_skpd","t_renja.id_skpd = m_skpd.id_skpd","inner");
		$this->db->join("m_bidkoordinasi","t_renja.id_bidkoor = m_bidkoordinasi.id_bidkoor","inner");
		$this->db->join("m_status_renja","t_renja.id_status = m_status_renja.id","inner");

		$result = $this->db->get();
		return $result->row();
	}*/

	function get_all_histories_renja($id_renja){
		$this->db->where("id_renja", $id_renja);
		$this->db->from($this->historynya);
		$this->db->order_by("create_date", "DESC");
		$result = $this->db->get();
		return $result->result();
	}

	/*
	function get_all_renja($search, $start, $length, $order, $id_skpd, $order_arr, $status = NULL, $detail = FALSE){
		$this->db->select("t_renja.*");
		$this->db->select("status_renja");
		$this->db->from($this->table);

		if ($id_skpd != "all") {
			$this->db->where("t_renja.id_skpd", $id_skpd);
		}
		if ($status=="BARU") {
			$this->db->where("id_status", $this->id_status_baru);
		}elseif ($status=="VERIFIKASI") {
			$this->db->where("id_status", $this->id_status_send);
		}elseif ($status=="APPROVED") {
			$this->db->where("id_status", $this->id_status_approved);
		}
		if (!is_null($search)) {
			$this->db->where("(tujuan LIKE '%". $search['value'] ."%' OR sasaran LIKE '%". $search['value'] ."%' OR indikator_sasaran LIKE '%". $search['value'] ."%' OR kd_urusan LIKE '%". $search['value'] ."%' OR kd_bidang LIKE '%". $search['value'] ."%' OR kd_program LIKE '%". $search['value'] ."%' OR kd_kegiatan LIKE '%". $search['value'] ."%' OR nm_urusan LIKE '%". $search['value'] ."%' OR nm_bidang LIKE '%". $search['value'] ."%' OR ket_program LIKE '%". $search['value'] ."%' OR ket_kegiatan LIKE '%". $search['value'] ."%' OR status_renja LIKE '%". $search['value'] ."%')");
		}

		if ($detail) {
			$this->db->select("m_bidkoordinasi.nama_koor");
			$this->db->select("m_skpd.nama_skpd");
			$this->db->join("m_skpd","t_renja.id_skpd = m_skpd.id_skpd","inner");
			$this->db->join("m_bidkoordinasi","t_renja.id_bidkoor = m_bidkoordinasi.id_bidkoor","inner");
		}

		if (!is_null($length) && !is_null($start)) {
			$this->db->limit($length, $start);
		}
		if (!is_null($order)) {
			$this->db->order_by($order_arr[$order["column"]], $order["dir"]);
		}

		$this->db->join("m_status_renja","t_renja.id_status = m_status_renja.id","inner");
		$result = $this->db->get();
		return $result->result();
	}

	function count_all_renja($search, $id_skpd, $status = NULL){
		$this->db->from($this->table);

		if ($id_skpd != "all") {
			$this->db->where("id_skpd", $id_skpd);
		}
		if ($status=="BARU") {
			$this->db->where("id_status", $this->id_status_baru);
		}elseif ($status=="VERIFIKASI") {
			$this->db->where("id_status", $this->id_status_send);
		}elseif ($status=="APPROVED") {
			$this->db->where("id_status", $this->id_status_approved);
		}
		if (!is_null($search)) {
			$this->db->where("(tujuan LIKE '%". $search['value'] ."%' OR sasaran LIKE '%". $search['value'] ."%' OR indikator_sasaran LIKE '%". $search['value'] ."%' OR kd_urusan LIKE '%". $search['value'] ."%' OR kd_bidang LIKE '%". $search['value'] ."%' OR kd_program LIKE '%". $search['value'] ."%' OR kd_kegiatan LIKE '%". $search['value'] ."%' OR nm_urusan LIKE '%". $search['value'] ."%' OR nm_bidang LIKE '%". $search['value'] ."%' OR ket_program LIKE '%". $search['value'] ."%' OR ket_kegiatan LIKE '%". $search['value'] ."%' OR status_renja LIKE '%". $search['value'] ."%')");
		}

		$this->db->join("m_status_renja","t_renja.id_status = m_status_renja.id","inner");
		$result = $this->db->count_all_results();
		return $result;
	}
	*/

	function get_all_id_renja_veri_or_approved_to_json($id_skpd){
		$this->db->select("GROUP_CONCAT(id) AS id");
		$this->db->where("id_status !=", $this->id_status_baru);
		$this->db->from($this->table);
		$result = $this->db->get();
		$data = $result->row();
		$id_array = explode(",", $data->id);
		return json_encode($id_array);
	}

	function get_all_renja_by_in($id, $noresult=FALSE){
		$this->db->select("t_renja.*");
		$this->db->select("status_renja");
		$this->db->where_in('t_renja.id', $id);
		$this->db->from($this->table);
		$this->db->join("m_status_renja","t_renja.id_status = m_status_renja.id","inner");
		$result = $this->db->get();
		if (!$noresult) {
			return $result->result();
		}else{
			return $result;
		}
	}

	function get_total_nominal_renja_by_in($id){
		$this->db->select('COUNT(t_renja.id) AS count');
		$this->db->select_sum('nominal_1');
		$this->db->select_sum('nominal_2');
		$this->db->select_sum('nominal_3');
		$this->db->select_sum('nominal_4');
		$this->db->where_in('t_renja.id', $id);
		$this->db->where("id_status", $this->id_status_approved);
		$this->db->from($this->table);
		$this->db->join("m_status_renja","t_renja.id_status = m_status_renja.id","inner");
		$result = $this->db->get();
		return $result->row();
	}

	function send_renja($id, $id_skpd){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->select("id");
		$this->db->from($this->table);
		$this->db->where_in('id', $id);
		$this->db->where("id_skpd", $id_skpd);
		$this->db->where("(id_status=". $this->id_status_baru ." OR id_status=". $this->id_status_revisi .")");
		$result = $this->db->get();
		$result = $result->result();

		foreach ($result as $value) {
			$id = $value->id;
			$this->db->set("id_status", $this->id_status_send);
			$this->db->where("id", $value->id);
			$this->db->update($this->table);

			$this->add_history($value->id, $this->send);
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function delete_sended_renja($id, $id_skpd){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->where("id_skpd", $id_skpd);
		$this->db->set("id_status", $this->id_status_baru);
		$result = $this->db->update($this->table);

		$this->add_history($id, $this->delete_from_sended_list);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}
	function get_indikator_prog_keg($id, $return_result=TRUE, $satuan=FALSE){
		$this->db->select($this->table_indikator_program.".*, satuan_target as nama_value");
		$this->db->where('id_prog_keg', $id);
		$this->db->from($this->table_indikator_program);

		if ($satuan) {
			// $this->db->select("m_lov.nama_value");
			// $this->db->join("m_lov",$this->table_indikator_program.".satuan_target = m_lov.kode_value AND kode_app='1'","inner");
		}

		$this->db->select("m_status_indikator.nama_status_indikator");
		$this->db->select("m_kategori_indikator.nama_kategori_indikator");
		$this->db->join("m_status_indikator",$this->table_indikator_program.".status_indikator = m_status_indikator.kode_status_indikator","inner");
		$this->db->join("m_kategori_indikator",$this->table_indikator_program.".kategori_indikator = m_kategori_indikator.kode_kategori_indikator","inner");

		$result = $this->db->get();
		if ($return_result) {
			return $result->result();
		}else{
			return $result;
		}
	}

	function get_indikator_prog_keg_vdee($id, $return_result=TRUE, $satuan=FALSE){
		$this->db->select($this->table_indikator_program.".*");
		$this->db->where('id_prog_keg', $id);
		$this->db->where(('(target > 0 OR target_thndpn > 0)'));
		$this->db->from($this->table_indikator_program);

		if ($satuan) {
		//	$this->db->select("m_lov.nama_value");
			$this->db->select("m_status_indikator.nama_status_indikator");
			$this->db->select("m_kategori_indikator.nama_kategori_indikator");
		//	$this->db->join("m_lov",$this->table_indikator_program.".satuan_target = m_lov.kode_value AND kode_app='1'","inner");
			$this->db->join("m_status_indikator",$this->table_indikator_program.".status_indikator = m_status_indikator.kode_status_indikator","inner");
			$this->db->join("m_kategori_indikator",$this->table_indikator_program.".kategori_indikator = m_kategori_indikator.kode_kategori_indikator","inner");
		}


		$result = $this->db->get();
		if ($return_result) {
			return $result->result();
		}else{
			return $result;
		}
	}

	function import_from_renstra($id_skpd, $ta, $id_tahun, $renja=NULL){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		$tahun1=0;
		$tahun1=$id_tahun+1;
		$ta_dpn = $ta + 1;

		if($tahun1 > 5){
			$tahun1=5;
		};




		# For program #
		$query="SELECT
					$ta AS tahun,
					t_renstra_prog_keg.id AS id_renstra,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi as lokasi_thndpn,
					penanggung_jawab,
					t_renstra_prog_keg.id_skpd,
					(Select sum(nominal_$id_tahun) from t_renstra_prog_keg a where t_renstra_prog_keg.id = a.parent) AS nominal,
					nominal_$tahun1	AS nominal_thndpn,
					t_renstra_prog_keg.id_prog_rpjmd
				FROM t_renstra_prog_keg INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra_prog_keg.is_prog_or_keg=1 AND t_renstra.id_skpd=?
				AND (SELECT SUM(nominal_$id_tahun) FROM t_renstra_prog_keg a WHERE t_renstra_prog_keg.id = a.parent)>0";
		$result = $this->db->query($query, $id_skpd);
		$renja_baru = $result->result_array();

		foreach ($renja_baru as $row) {

			$this->db->insert("t_renja_prog_keg", $row);
			$new_id = $this->db->insert_id();

			$query = "INSERT INTO t_renja_indikator_prog_keg(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn, status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn, id_indikator_renstra)
			SELECT ?, indikator,indikator, satuan_target, satuan_target, kode_positif_negatif, kode_positif_negatif, kode_kategori_indikator, kode_kategori_indikator, target_$id_tahun, target_$tahun1, t_renstra_indikator_prog_keg.id FROM t_renstra_indikator_prog_keg WHERE id_prog_keg=?";
			$result = $this->db->query($query, array($new_id, $row['id_renstra']));

			# For kegiatan #
			$query="SELECT
					$ta AS tahun,
					t_renstra_prog_keg.id AS id_renstra,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi AS lokasi_thndpn,
					penanggung_jawab,
					t_renstra_prog_keg.id_skpd,
					nominal_$id_tahun AS nominal,
					nominal_$tahun1	AS nominal_thndpn,
					uraian_kegiatan_$id_tahun AS catatan,
					uraian_kegiatan_$tahun1 AS catatan_thndpn,
					t_renstra_prog_keg.id_prog_rpjmd,
					? AS parent
				FROM t_renstra_prog_keg INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra_prog_keg.is_prog_or_keg=2 AND t_renstra_prog_keg.parent=? AND t_renstra.id_skpd=?
				AND nominal_$id_tahun > 0";
			$result = $this->db->query($query, array($new_id, $row['id_renstra'], $id_skpd));
			$kegiatan_renja_baru = $result->result_array();

			foreach ($kegiatan_renja_baru as $row1) {
				$id_renstra_nya =  $row['id_renstra'];

				$this->db->insert("t_renja_prog_keg", $row1);
				$new_id = $this->db->insert_id();


				$query = "INSERT INTO t_renja_indikator_prog_keg(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn, status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn, id_indikator_renstra)
				SELECT ?, indikator, indikator, satuan_target, satuan_target, kode_positif_negatif, kode_positif_negatif, kode_kategori_indikator, kode_kategori_indikator, target_$id_tahun, target_$tahun1, t_renstra_indikator_prog_keg.id FROM t_renstra_indikator_prog_keg WHERE id_prog_keg=?";
				$result = $this->db->query($query, array($new_id, $row1['id_renstra']));
				//untuk belanja

				$query2 = "INSERT INTO `t_renja_belanja_kegiatan` (
										  `id_renstra`,
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date`,
										  `is_tahun_sekarang`,
										  `id_status_renja`,
										  `id_keg`
										)
										SELECT
										  '$id_renstra_nya',
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date` ,
										  '1','1','$new_id' FROM t_renstra_belanja_kegiatan WHERE tahun = '$ta' AND  id_kegiatan = ?";
				$result2 =  $this->db->query($query2, $row1['id_renstra']);
				//belanja 2
				$query2 = "INSERT INTO `t_renja_belanja_kegiatan` (
										  `id_renstra`,
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date`,
										  `is_tahun_sekarang`,
										  `id_status_renja`,
										  `id_keg`
										)
										SELECT
										  '$id_renstra_nya',
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date` ,
										  '0','1','$new_id' FROM t_renstra_belanja_kegiatan WHERE tahun = '$ta_dpn' AND  id_kegiatan = ?";
				$result2 =  $this->db->query($query2, $row1['id_renstra']);
			}


		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function import_from_renstra_sub_unit($id_skpd, $ta, $id_tahun, $renja=NULL){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		$tahun1=0;
		$tahun1=$id_tahun+1;
		$ta_dpn = $ta + 1;

		if($tahun1 > 5){
			$tahun1=5;
			$ta_dpn = $ta_dpn - 1;
		};




		# For program #
		$query="SELECT
					$ta AS tahun,
					t_renstra_prog_keg.id AS id_renstra,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi as lokasi_thndpn,
					penanggung_jawab,
					t_renstra_prog_keg.id_skpd,
					(Select sum(nominal_$id_tahun) from t_renstra_prog_keg a where t_renstra_prog_keg.id = a.parent) AS nominal,
					nominal_$tahun1	AS nominal_thndpn,
					t_renstra_prog_keg.id_prog_rpjmd
				FROM t_renstra_prog_keg INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra_prog_keg.is_prog_or_keg=1 AND t_renstra_prog_keg.id_skpd=?
				AND (SELECT SUM(nominal_$id_tahun) FROM t_renstra_prog_keg a WHERE t_renstra_prog_keg.id = a.parent)>0";
		$result = $this->db->query($query, $id_skpd);
		$renja_baru = $result->result_array();
		
		foreach ($renja_baru as $row) {

			$this->db->insert("t_renja_prog_keg", $row);
			$new_id = $this->db->insert_id();

			$query = "INSERT INTO t_renja_indikator_prog_keg(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn, status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn, id_indikator_renstra)
			SELECT ?, indikator,indikator, satuan_target, satuan_target, kode_positif_negatif, kode_positif_negatif, kode_kategori_indikator, kode_kategori_indikator, target_$id_tahun, target_$tahun1, t_renstra_indikator_prog_keg.id FROM t_renstra_indikator_prog_keg WHERE id_prog_keg=?";
			$result = $this->db->query($query, array($new_id, $row['id_renstra']));

			# For kegiatan #
			$query="SELECT
					$ta AS tahun,
					t_renstra_prog_keg.id AS id_renstra,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi AS lokasi_thndpn,
					penanggung_jawab,
					t_renstra_prog_keg.id_skpd,
					nominal_$id_tahun AS nominal,
					nominal_$tahun1	AS nominal_thndpn,
					uraian_kegiatan_$id_tahun AS catatan,
					uraian_kegiatan_$tahun1 AS catatan_thndpn,
					t_renstra_prog_keg.id_prog_rpjmd,
					? AS parent
				FROM t_renstra_prog_keg INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra_prog_keg.is_prog_or_keg=2 AND t_renstra_prog_keg.parent=? AND t_renstra_prog_keg.id_skpd=?
				AND nominal_$id_tahun > 0";
			$result = $this->db->query($query, array($new_id, $row['id_renstra'], $id_skpd));
			$kegiatan_renja_baru = $result->result_array();

			foreach ($kegiatan_renja_baru as $row1) {
				$id_renstra_nya =  $row['id_renstra'];

				$this->db->insert("t_renja_prog_keg", $row1);
				$new_id = $this->db->insert_id();


				$query = "INSERT INTO t_renja_indikator_prog_keg(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn, status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn, id_indikator_renstra)
				SELECT ?, indikator, indikator, satuan_target, satuan_target, kode_positif_negatif, kode_positif_negatif, kode_kategori_indikator, kode_kategori_indikator, target_$id_tahun, target_$tahun1, t_renstra_indikator_prog_keg.id FROM t_renstra_indikator_prog_keg WHERE id_prog_keg=?";
				$result = $this->db->query($query, array($new_id, $row1['id_renstra']));
				//untuk belanja

				$query2 = "INSERT INTO `t_renja_belanja_kegiatan` (
										  `id_renstra`,
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date`,
										  `is_tahun_sekarang`,
										  `id_status_renja`,
										  `id_keg`
										)
										SELECT
										  '$id_renstra_nya',
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date` ,
										  '1','1','$new_id' FROM t_renstra_belanja_kegiatan WHERE tahun = '$ta' AND  id_kegiatan = ?";
				$result2 =  $this->db->query($query2, $row1['id_renstra']);
				//belanja 2
				$query2 = "INSERT INTO `t_renja_belanja_kegiatan` (
										  `id_renstra`,
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date`,
										  `is_tahun_sekarang`,
										  `id_status_renja`,
										  `id_keg`
										)
										SELECT
										  '$id_renstra_nya',
										  `tahun`,
										  `kode_urusan`,
										  `kode_bidang`,
										  `kode_program`,
										  `kode_kegiatan`,
										  `kode_sumber_dana`,
										  `kode_jenis_belanja`,
										  `kode_kategori_belanja`,
										  `kode_sub_kategori_belanja`,
										  `kode_belanja`,
										  `uraian_belanja`,
										  `detil_uraian_belanja`,
										  `volume`,
										  `satuan`,
										  `nominal_satuan`,
										  `subtotal`,
										  `created_date` ,
										  '0','1','$new_id' FROM t_renstra_belanja_kegiatan WHERE tahun = '$ta_dpn' AND  id_kegiatan = ?";
				$result2 =  $this->db->query($query2, $row1['id_renstra']);
			}


		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function insert_renja($id_skpd, $ta){
		$created_date = date("Y-m-d H:i:s");
		$created_by = $this->session->userdata('username');
		$this->db->set('id_skpd', $id_skpd);
		$this->db->set('tahun', $ta);
		$this->db->set('created_date', $created_date);
		$this->db->set('created_by', $created_by);
		$this->db->insert('t_renja');
		return $this->db->insert_id();
	}

	function get_renja_belanja_per_tahun($id, $is_tahun){
		//------- query by deesudi
			$query = $this->db->query("SELECT id ,tahun,
							kode_sumber_dana AS kode_sumber_dana,(
								SELECT sumber_dana FROM m_sumber_dana WHERE id = kode_sumber_dana
							) AS sumberDana,
							kode_jenis_belanja AS kode_jenis_belanja, (
								SELECT jenis_belanja FROM m_jenis_belanja WHERE kd_jenis_belanja = kode_jenis_belanja
							) AS jenis,
							kode_kategori_belanja AS kode_kategori_belanja, (
								SELECT kategori_belanja FROM m_kategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja
							) AS kategori,
							kode_sub_kategori_belanja AS kode_sub_kategori_belanja,(
								SELECT sub_kategori_belanja FROM m_subkategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja
							) AS subkategori,
							kode_belanja AS kode_belanja,(
								SELECT belanja FROM m_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja AND kd_belanja = kode_belanja
							) AS belanja,
							uraian_belanja,detil_uraian_belanja,volume,satuan,nominal_satuan,subtotal,is_tahun_sekarang,id_keg
							FROM t_renja_belanja_kegiatan
							WHERE is_tahun_sekarang = '$is_tahun' AND id_keg = '$id'
							ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC");
		return $query->result();
	}

	function get_renja_belanja_per_tahun221($tahun_pilihan, $is_tahun, $idK){
		//------- query by deesudi
		 // print_r($tahun_pilihan);
		 // exit();
			$query = $this->db->query("SELECT id ,tahun,
							kode_sumber_dana AS kode_sumber_dana,(
								SELECT sumber_dana FROM m_sumber_dana WHERE id = kode_sumber_dana
							) AS sumberDana,
							kode_jenis_belanja AS kode_jenis_belanja, (
								SELECT jenis_belanja FROM m_jenis_belanja WHERE kd_jenis_belanja = kode_jenis_belanja
							) AS jenis,
							kode_kategori_belanja AS kode_kategori_belanja, (
								SELECT kategori_belanja FROM m_kategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja
							) AS kategori,
							kode_sub_kategori_belanja AS kode_sub_kategori_belanja,(
								SELECT sub_kategori_belanja FROM m_subkategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja
							) AS subkategori,
							kode_belanja AS kode_belanja,(
								SELECT belanja FROM m_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja AND kd_belanja = kode_belanja
							) AS belanja,(
								SELECT Kd_Fungsi FROM m_bidang WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang
							) AS kode_fungsi,(
								SELECT Nm_Fungsi FROM m_fungsi WHERE Kd_Fungsi = kode_fungsi
							) AS nama_fungsi,(
								SELECT Nm_Urusan FROM m_urusan WHERE Kd_Urusan = kode_urusan
							) AS nama_urusan,(
								SELECT Ket_Program FROM m_program WHERE Kd_Urusan = kode_urusan  AND Kd_Bidang = kode_bidang AND Kd_Prog = kode_program
							) AS nama_program,(
								SELECT Ket_Kegiatan FROM m_kegiatan WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang AND Kd_Prog = kode_program AND Kd_Keg = kode_kegiatan
							) AS nama_kegiatan,(
							SELECT Nm_Bidang FROM m_bidang WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang
							) AS nama_bidang,(
							SELECT id FROM m_tahun_anggaran WHERE tahun_anggaran = '$tahun_pilihan'
							) AS tahun_anggaran,(
							SELECT nominal FROM `t_renja_prog_keg` WHERE id = '$idK'
							) AS nominal_tahun,

							uraian_belanja,detil_uraian_belanja,volume,satuan,nominal_satuan,subtotal,tahun,id_keg , kode_urusan , kode_bidang , kode_program, kode_kegiatan, REPLACE(UPPER(uraian_belanja), ' ','') as uraian_upper
							FROM t_renja_belanja_kegiatan
							WHERE tahun = '$tahun_pilihan' and is_tahun_sekarang = '$is_tahun' and id_keg = '$idK'
							ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC");
		return $query->result();

	}

	function get_indikator_keluaran($ta, $idK){
			$query = $this->db->query("SELECT * FROM `t_renja_indikator_prog_keg` WHERE   `id_prog_keg` = '$idK'");
		return $query->result();
	}

	function get_indikator_capaian( $idP){
			$query = $this->db->query("SELECT * FROM `t_renja_indikator_prog_keg` WHERE   `id_prog_keg` = '$idP'");
		return $query->result();
	}

	function get_nominal_renja( $idK, $is_tahun_sekarang){

		if ($is_tahun_sekarang == 1) {
			$query = $this->db->query("SELECT  nominal_thndpn ,
									CASE WHEN
									(SELECT
										 nominal FROM t_renja_prog_keg WHERE  id = '$idK' AND tahun  =  tahun-1
									) IS NULL THEN 0 ELSE
									(SELECT
										 nominal FROM t_renja_prog_keg WHERE  id = '$idK' AND tahun  =  tahun-1
									) END AS nominal_min , nominal
									FROM `t_renja_prog_keg` WHERE id = '$idK' ");
		}else{
			$query = $this->db->query("SELECT  nominal_thndpn as nominal
									 , nominal as nominal_min , 0 as nominal_thndpn
									FROM `t_renja_prog_keg` WHERE id = '$idK' ");

		}

		return $query->result();
	}

	######################## FOR VERIFIKASI AWAL ########################
	function get_all_renja_veri(){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "
		SELECT t_renja_prog_keg.*, m_skpd.*, COUNT(t_renja_prog_keg.id) AS jum_semua,
		       SUM(IF(t_renja_prog_keg.id_status=?,1,0)) AS jum_dikirim
	    FROM t_renja_prog_keg
		LEFT JOIN m_skpd ON t_renja_prog_keg.id_skpd=m_skpd.id_skpd
		WHERE t_renja_prog_keg.is_prog_or_keg=?
		AND t_renja_prog_keg.tahun=?
		AND t_renja_prog_keg.`id_status`='2'
		GROUP BY m_skpd.id_skpd";
		$data = array($this->id_status_send, $this->is_kegiatan, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_data_renja($id_skpd){
		$ta = $this->m_settings->get_tahun_anggaran();

		//$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg INNER JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra.id_skpd=? AND t_renja_prog_keg.tahun=? AND t_renja_prog_keg.id_status =? ORDER BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, t_renja_prog_keg.kd_program, t_renja_prog_keg.kd_kegiatan";
		$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.`id`=t_renja_prog_keg.`id_renstra`
					LEFT JOIN t_renstra ON t_renstra_prog_keg.`id_renstra`=t_renstra.`id`
					WHERE t_renja_prog_keg.`id_skpd`= ?
					AND t_renja_prog_keg.`tahun`= ?
					AND t_renja_prog_keg.`id_status`= ?
					ORDER BY t_renja_prog_keg.kd_urusan,
						t_renja_prog_keg.kd_bidang,
						t_renja_prog_keg.kd_program,
						t_renja_prog_keg.kd_kegiatan";
		$result = $this->db->query($query, array($id_skpd, $ta, $this->id_status_send));
		return $result->result();
	}

	function get_one_renja_veri($id){
		$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg WHERE id=?";
		$result = $this->db->query($query, array($id));
		return $result->row();
	}

	function approved_renja($id){
		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array('id_status'=>$this->id_status_approved));
		return $return;
	}

	function not_approved_renja($id, $ket){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array('id_status'=>$this->id_status_revisi));

		$result = $this->db->insert("t_renja_revisi", array('id_renja' => $id, 'ket' => $ket));

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function disapprove_renja($id, $ket){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$query = "INSERT t_renja_revisi SELECT NULL,
					t_renja_prog_keg.id, ?
					FROM t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
					LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
					WHERE t_renja_prog_keg.id_skpd=?";
		$data = array($ket, $id);
		$result = $this->db->query($query, $data);

		$query = "UPDATE t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
					LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
					SET t_renja_prog_keg.id_status=3
					WHERE t_renja_prog_keg.id_skpd=?
					AND t_renja_prog_keg.id_status=?";
		$data = array($id, $this->id_status_send);
		$result = $this->db->query($query, $data);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	######################## FOR VERIFIKASI AKHIR ########################
	function get_all_renja_veri_akhir(){
		$ta = $this->m_settings->get_tahun_anggaran();
		$query = "
		SELECT t_renja_prog_keg.*, m_skpd.*, COUNT(t_renja_prog_keg.id) AS jum_semua,
		       SUM(IF(t_renja_prog_keg.id_status=?,1,0)) AS jum_dikirim
	    FROM t_renja_prog_keg
		INNER JOIN m_skpd ON t_renja_prog_keg.id_skpd=m_skpd.id_skpd
		WHERE t_renja_prog_keg.is_prog_or_keg=?
		AND t_renja_prog_keg.tahun=?
		AND t_renja_prog_keg.`id_status` = '4'
		GROUP BY m_skpd.id_skpd ";
		$data = array($this->id_status_approved, $this->is_kegiatan, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_data_renja_akhir($id_skpd){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
					LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
				WHERE t_renja_prog_keg.id_skpd=?
				AND t_renja_prog_keg.tahun=?
				AND t_renja_prog_keg.id_status=?
				AND t_renja_prog_keg.is_prog_or_keg=?
				ORDER BY t_renja_prog_keg.kd_urusan,
				t_renja_prog_keg.kd_bidang,
				t_renja_prog_keg.kd_program,
				t_renja_prog_keg.kd_kegiatan";
		$result = $this->db->query($query, array($id_skpd, $ta, $this->id_status_approved, $this->is_kegiatan));
		return $result->result();
	}

	function not_approved_veri_akhir_renja($id, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		//$this->db->where($this->table_program_kegiatan.".parent", $id);
		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array($this->table_program_kegiatan.'.id_status'=>$this->id_status_revisi2));

		$result = $this->db->insert("t_renja_revisi_keg", $data);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function approved_veri_akhir_renja($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		//$this->db->where($this->table_program_kegiatan.".parent", $id);
		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array($this->table_program_kegiatan.'.id_status'=>$this->id_status_approved2));


		$query = "SELECT vw1.id, vw1.parent FROM t_renja_prog_keg INNER JOIN t_renja_prog_keg AS vw1 ON t_renja_prog_keg.parent=vw1.parent WHERE t_renja_prog_keg.id=? AND vw1.id_status!=?";
		$return = $this->db->query($query, array($id, $this->id_status_approved2));
		if ($return->num_rows() == 0) {
			$query = "SELECT * FROM t_renja_prog_keg WHERE t_renja_prog_keg.id=?";
			$return = $this->db->query($query, array($id));
			$keg = $return->row();

			$this->db->where($this->table_program_kegiatan.".id", $keg->parent);
			$return = $this->db->update($this->table_program_kegiatan, array($this->table_program_kegiatan.'.id_status'=>$this->id_status_approved2));
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_urusan_skpd($ta,$id_skpd){
		$where="";
		if (!empty($id_skpd) && $id_skpd!="all") {
			$where=" WHERE keg.id_skpd='". $id_skpd ."'AND keg.tahun = ". $ta;
		}
		else
		{
			$where="WHERE keg.tahun = ".$ta;
		}
		$query = "select t.*,u.Nm_Urusan as nama_urusan from (
			SELECT
				keg.kd_urusan,keg.kd_bidang,keg.kd_program,keg.kd_kegiatan,
				SUM(keg.nominal) AS sum_nominal,
				SUM(keg.nominal_thndpn) AS sum_nominal_thndpn
			FROM
				(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=1 AND id_skpd > 0) AS pro
			INNER JOIN
				(SELECT * FROM t_renja_prog_keg WHERE is_prog_or_keg=2 AND id_skpd > 0 AND id IN (SELECT id_prog_keg 
FROM t_renja_indikator_prog_keg WHERE target > 0)) AS keg ON keg.parent=pro.id
			".$where."
			AND pro.id IN (SELECT id_prog_keg FROM t_renja_indikator_prog_keg WHERE id_prog_keg = pro.id AND target > 0)
			GROUP BY keg.kd_urusan
			ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC
			) t
			left join m_urusan u
			on t.kd_urusan = u.Kd_Urusan
		";
		$result = $this->db->query($query);
		return $result->result();
	}

	############################FOR PREVIEW RANWAL READ ONLY#####################################
	function get_all_renja_veri_readonly(){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "
		SELECT t_renja_prog_keg.*, m_skpd.*, COUNT(t_renja_prog_keg.id) AS jum_semua,
		       SUM(IF(t_renja_prog_keg.id_status=?,1,0)) AS jum_dikirim
	    FROM t_renja_prog_keg
		LEFT JOIN m_skpd ON t_renja_prog_keg.id_skpd=m_skpd.id_skpd
		WHERE t_renja_prog_keg.is_prog_or_keg=?
		AND t_renja_prog_keg.tahun=?
		AND t_renja_prog_keg.`id_status`>='2'
		GROUP BY m_skpd.id_skpd";
		$data = array($this->id_status_send, $this->is_kegiatan, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_data_renja_readonly($id_skpd){
		$ta = $this->m_settings->get_tahun_anggaran();

		//$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg INNER JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra.id_skpd=? AND t_renja_prog_keg.tahun=? AND t_renja_prog_keg.id_status =? ORDER BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, t_renja_prog_keg.kd_program, t_renja_prog_keg.kd_kegiatan";
		$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.`id`=t_renja_prog_keg.`id_renstra`
					LEFT JOIN t_renstra ON t_renstra_prog_keg.`id_renstra`=t_renstra.`id`
					WHERE t_renja_prog_keg.`id_skpd`= ?
					AND t_renja_prog_keg.`tahun`= ?
					AND t_renja_prog_keg.`id_status`>= ?
					ORDER BY t_renja_prog_keg.kd_urusan,
						t_renja_prog_keg.kd_bidang,
						t_renja_prog_keg.kd_program,
						t_renja_prog_keg.kd_kegiatan";
		$result = $this->db->query($query, array($id_skpd, $ta, $this->id_status_send));
		return $result->result();
	}

	function get_all_renja_veri_akhir_readonly(){
		$ta = $this->m_settings->get_tahun_anggaran();
		$query = "
		SELECT t_renja_prog_keg.*, m_skpd.*, COUNT(t_renja_prog_keg.id) AS jum_semua,
		       SUM(IF(t_renja_prog_keg.id_status=?,1,0)) AS jum_dikirim
	    FROM t_renja_prog_keg
		INNER JOIN m_skpd ON t_renja_prog_keg.id_skpd=m_skpd.id_skpd
		WHERE t_renja_prog_keg.is_prog_or_keg=?
		AND t_renja_prog_keg.tahun=?
		AND t_renja_prog_keg.`id_status` >= '4'
		GROUP BY m_skpd.id_skpd ";
		$data = array($this->id_status_approved, $this->is_kegiatan, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_data_renja_akhir_readonly($id_skpd){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg
					LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
					LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
				WHERE t_renja_prog_keg.id_skpd=?
				AND t_renja_prog_keg.tahun=?
				AND t_renja_prog_keg.id_status>=?
				AND t_renja_prog_keg.is_prog_or_keg=?
				ORDER BY t_renja_prog_keg.kd_urusan,
				t_renja_prog_keg.kd_bidang,
				t_renja_prog_keg.kd_program,
				t_renja_prog_keg.kd_kegiatan";
		$result = $this->db->query($query, array($id_skpd, $ta, $this->id_status_approved, $this->is_kegiatan));
		return $result->result();
	}

	function get_kegiatan($id_kegiatan, $tahun=NULL, $is_tahun=NULL, $not_in=NULL){
				$th = "";
				$not = "";
				if (!empty($tahun)) {
					$th = " AND tahun = '".$tahun."' AND is_tahun_sekarang = '".$is_tahun."'";
				}
				if(!empty($not_in)){
					$not = " AND id <> '".$not_in."' ";
				}

				$query = "SELECT id ,tahun,
								kode_sumber_dana AS kode_sumber_dana,(
									SELECT sumber_dana FROM m_sumber_dana WHERE id = kode_sumber_dana
								) AS Sumber_dana,
								kode_jenis_belanja AS kode_jenis_belanja, (
									SELECT jenis_belanja FROM m_jenis_belanja WHERE kd_jenis_belanja = kode_jenis_belanja
								) AS jenis_belanja,
								kode_kategori_belanja AS kode_kategori_belanja, (
									SELECT kategori_belanja FROM m_kategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja
								) AS kategori_belanja,
								kode_sub_kategori_belanja AS kode_sub_kategori_belanja,(
									SELECT sub_kategori_belanja FROM m_subkategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja
								) AS sub_kategori_belanja,
								kode_belanja AS kode_belanja,(
									SELECT belanja FROM m_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja AND kd_belanja = kode_belanja
								) AS belanja,
								uraian_belanja, detil_uraian_belanja, volume, satuan, nominal_satuan, subtotal, id_keg
								FROM t_renja_belanja_kegiatan
								WHERE id_keg = '$id_kegiatan' ".$th." ".$not." 
								ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC";

		$result = $this->db->query($query);
		return $result->result();
	}

	function get_one_belanja($id_belanja){
		$query = "SELECT id ,tahun, id_renstra,
				kode_sumber_dana AS kode_sumber_dana,(
					SELECT sumber_dana FROM m_sumber_dana WHERE id = kode_sumber_dana
				) AS Sumber_dana,
				kode_jenis_belanja AS kode_jenis_belanja, (
					SELECT jenis_belanja FROM m_jenis_belanja WHERE kd_jenis_belanja = kode_jenis_belanja
				) AS jenis_belanja,
				kode_kategori_belanja AS kode_kategori_belanja, (
					SELECT kategori_belanja FROM m_kategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja
				) AS kategori_belanja,
				kode_sub_kategori_belanja AS kode_sub_kategori_belanja,(
					SELECT sub_kategori_belanja FROM m_subkategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja
				) AS sub_kategori_belanja,
				kode_belanja AS kode_belanja,(
					SELECT belanja FROM m_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja AND kd_belanja = kode_belanja
				) AS belanja,
				uraian_belanja, detil_uraian_belanja, volume, satuan, nominal_satuan, subtotal, id_keg
				FROM t_renja_belanja_kegiatan
				WHERE id = '$id_belanja'
				ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC";

		$result = $this->db->query($query);
		return $result->row();
	}

	function add_belanja_kegiatan($data, $id_belanja){
		if (!empty($id_belanja)) {
			$this->db->where('id', $id_belanja);
			$this->db->update('t_renja_belanja_kegiatan', $data);
		}else{
			$this->db->insert('t_renja_belanja_kegiatan', $data);
		}
	}

	function delete_one_kegiatan($id){
		$this->db->query('DELETE FROM t_renja_belanja_kegiatan WHERE id = "'.$id.'"');
	}

	function get_kegiatan_for_211_new($ta, $idK){
		$query = $this->db->query("SELECT kd_urusan AS kode_urusan, kd_bidang AS kode_bidang, kd_program AS kode_program, kd_kegiatan AS kode_kegiatan
			,(SELECT Kd_Fungsi FROM m_bidang WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang) AS kode_fungsi
			,(SELECT Nm_Fungsi FROM m_fungsi WHERE Kd_Fungsi = kode_fungsi) AS nama_fungsi
			,(SELECT Nm_Urusan FROM m_urusan WHERE Kd_Urusan = kode_urusan) AS nama_urusan
			,(SELECT Nm_Bidang FROM m_bidang WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang) AS nama_bidang
			,(SELECT Ket_Program FROM m_program WHERE Kd_Urusan = kode_urusan  AND Kd_Bidang = kode_bidang AND Kd_Prog = kode_program) AS nama_program
			,(SELECT Ket_Kegiatan FROM m_kegiatan WHERE Kd_Urusan = kode_urusan AND Kd_Bidang = kode_bidang AND Kd_Prog = kode_program AND Kd_Keg = kode_kegiatan) AS nama_kegiatan
			,nominal,nominal_thndpn, parent, lokasi
			FROM t_renja_prog_keg
			WHERE id = '$idK'");
		return $query->row();
	}

	function get_belanja_for_221_new($ta, $is_thn, $idK){
		$query = $this->db->query("SELECT kode_sumber_dana AS kode_sumber_dana
			,(SELECT sumber_dana FROM m_sumber_dana WHERE id = kode_sumber_dana) AS sumberDana
			,kode_jenis_belanja AS kode_jenis_belanja
			,(SELECT jenis_belanja FROM m_jenis_belanja WHERE kd_jenis_belanja = kode_jenis_belanja) AS jenis
			,kode_kategori_belanja AS kode_kategori_belanja
			,(SELECT kategori_belanja FROM m_kategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja) AS kategori
			,kode_sub_kategori_belanja AS kode_sub_kategori_belanja
			,(SELECT sub_kategori_belanja FROM m_subkategori_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja) AS subkategori
			,kode_belanja AS kode_belanja,(SELECT belanja FROM m_belanja WHERE kd_jenis_belanja = kode_jenis_belanja AND kd_kategori_belanja = kode_kategori_belanja AND kd_subkategori_belanja = kode_sub_kategori_belanja AND kd_belanja = kode_belanja) AS belanja
			,uraian_belanja, REPLACE(UPPER(uraian_belanja), ' ','') AS uraian_upper
			,detil_uraian_belanja, volume, satuan, nominal_satuan, subtotal
			FROM t_renja_belanja_kegiatan
			WHERE tahun = '$ta' AND id_keg = '$idK' AND is_tahun_sekarang = '$is_thn'
			ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC, uraian_upper ASC, detil_uraian_belanja ASC");
		return $query->result();
	}
}
?>
