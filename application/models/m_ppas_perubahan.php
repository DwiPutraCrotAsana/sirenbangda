<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_ppas_perubahan extends CI_Model
{
	var $table_rka = 't_ppas_perubahan';
	var $table = 't_ppas_perubahan';
	var $table_urusan = 'm_urusan';
	var $table_bidang = 'm_bidang';
	var $table_program = 'm_program';
	var $table_kegiatan = 'm_kegiatan';
	var $primary_rka = 'id';

	var $table_program_kegiatan = 't_ppas_prog_keg_perubahan';
	var $table_indikator_program = 't_ppas_indikator_prog_keg';
	var $table_indikator_program_perubahan = 't_ppas_indikator_prog_keg_perubahan';
	var $is_program = 1;
	var $is_kegiatan = 2;

	var $id_status_baru = "1";
	var $id_status_send = "2";
	var $id_status_revisi = "3";
	var $id_status_approved = "4";

	var $history_renja = 't_ppas_history_perubahan';


	function count_jendela_kontrol($id_skpd,$ta){
		if($this->session->userdata("id_skpd") > 100){
			$id_skpd = $this->session->userdata("id_skpd");
			$search = "AND t_ppas_prog_keg_perubahan.id_skpd in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')";
		}else {
			$kode_unit = $this->session->userdata("id_skpd");
			if ($id_skpd == $kode_unit) {
				$search = "AND t_ppas_prog_keg_perubahan.id_skpd in (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')";
			}else {
				$search = "AND (t_ppas_prog_keg_perubahan.id_skpd = '$id_skpd' OR t_ppas_prog_keg_perubahan.id_skpd = '$kode_unit')";
			}
		}
		$query = "SELECT
						SUM(IF(t_ppas_prog_keg_perubahan.id_status=?, 1, 0)) as baru,
						SUM(IF(t_ppas_prog_keg_perubahan.id_status>=?, 1, 0)) as kirim,
						SUM(IF(t_ppas_prog_keg_perubahan.id_status>?, 1, 0)) as proses,
						SUM(IF(t_ppas_prog_keg_perubahan.id_status=?, 1, 0)) as revisi,
						SUM(IF(t_ppas_prog_keg_perubahan.id_status>=?, 1, 0)) as veri
					FROM
						t_ppas_prog_keg_perubahan
					WHERE
						tahun = ? ".$search;
		$data = array(
					$this->id_status_baru,
					$this->id_status_send,
					$this->id_status_send,
					$this->id_status_revisi,
					$this->id_status_approved,
					$ta,$id_skpd, $this->is_kegiatan);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	private function add_history_renja($id_rka, $status, $keterangan=NULL){
		$history = array('id_rka' => $id_rka, 'id_status' => $status, 'create_date'=>date("Y-m-d H:i:s"),
		'user'=>$this->session->userdata('username'));
		if (!empty($keterangan)) {
			$history['keterangan'] = $keterangan;
		}
		$result = $this->db->insert($this->history_renja, $history);
		return $result;
	}

	function kirim_kendali_renja($id_skpd,$ta) {
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		$data_renja = $this->get_rka_skpd($id_skpd,$ta);
		//echo $this->db->last_query();
		foreach ($data_renja as $renja){
			if($renja->id_status=='1' || $renja->id_status=='3'){
				$this->update_status($renja->id,'2');
				$this->add_history_renja($renja->id, $this->id_status_send);
			}

			/*else if ($renja->id_status=='3'){
				$this->update_status($renja->id,'2');
			}*/
		}
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_one_rka_veri($id){
		$query = "SELECT t_ppas_prog_keg_perubahan.* FROM t_ppas_prog_keg_perubahan WHERE id=?";
		$result = $this->db->query($query, array($id));
		return $result->row();
	}

	function disapprove_renja($id_skpd,$ta) {
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();
		$data_renja = $this->get_rka_skpd($id_skpd,$ta);
		//echo $this->db->last_query();
		foreach ($data_renja as $renja){
			if($renja->id_status=='2'){
				$this->update_status($renja->id,'3');
				$this->add_history_renja($renja->id, $this->id_status_revisi,'data tidak valid keseluruhan');
			}

			/*else if ($renja->id_status=='3'){
				$this->update_status($renja->id,'2');
			}*/
		}
		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function approved_renja($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array('id_status'=>$this->id_status_approved));
		$this->add_history_renja($id, $this->id_status_approved);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function not_approved_renja($id, $ket){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where($this->table_program_kegiatan.".id", $id);
		$return = $this->db->update($this->table_program_kegiatan, array('id_status'=>$this->id_status_revisi));
		$this->add_history_renja($id, $this->id_status_approved, $ket);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}


	function get_rka_skpd($id_skpd,$ta)
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

	function update_status($id, $id_status)
     {
		$this->db->set('id_status',$id_status);
		$this->db->where('id', $id);
		$result=$this->db->update('t_ppas_prog_keg_perubahan');
		return $result;
	 }

	function get_rka($id_skpd,$ta)
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
	function get_all_program($id_skpd,$ta){
		if ($this->session->userdata("id_skpd") > 100) {
			$id_skpd = $this->session->userdata("id_skpd");
			$query = "SELECT * FROM (`$this->table_program_kegiatan`)
			WHERE `id_skpd` in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')
			AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";

			$result = $this->db->query($query);
		}else {
			$id_skpd = $this->m_skpd->get_kode_unit($id_skpd);
			$query = "SELECT * FROM (`$this->table_program_kegiatan`)
			WHERE `id_skpd` = '$id_skpd'
			AND `tahun` = '$ta' AND `is_prog_or_keg` = $this->is_program
			ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc";

			$result = $this->db->query($query);
			// $cek = $this->m_skpd->get_kode_unit($id_skpd);
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

	function insert_rka($id_skpd, $ta){
		$created_date = date("Y-m-d H:i:s");
		$created_by = $this->session->userdata('username');
		$this->db->set('id_skpd', $id_skpd);
		$this->db->set('tahun', $ta);
		$this->db->set('created_date', $created_date);
		$this->db->set('created_by', $created_by);
		$this->db->insert('t_ppas_perubahan');
		return $this->db->insert_id();
	}

	function import_from_renja($id_skpd, $ta){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();


		# For program #
		$query="SELECT
					$ta AS tahun,
					t_renja_prog_keg_perubahan.id AS id_renja,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi as lokasi_thndpn,
					penanggung_jawab,
					t_renja_prog_keg_perubahan.id_skpd,
					nominal,
					nominal_thndpn,
					t_renja_prog_keg_perubahan.id_prog_rpjmd
				FROM t_renja_prog_keg_perubahan
					WHERE t_renja_prog_keg_perubahan.is_prog_or_keg=1 AND
					tahun=$ta AND
					t_renja_prog_keg_perubahan.id_skpd in (SELECT id_skpd FROM m_skpd WHERE kode_unit = ?)";
		$result = $this->db->query($query, $id_skpd);
		$rka_baru = $result->result_array();

		foreach ($rka_baru as $row) {
			$this->db->insert("t_ppas_prog_keg_perubahan", $row);
			$new_id = $this->db->insert_id();

			$query = "INSERT INTO t_ppas_indikator_prog_keg_perubahan
				(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn,status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn,id_indikator_renja)
			SELECT ?, indikator,indikator, satuan_target,satuan_target, status_indikator, status_indikator, kategori_indikator, kategori_indikator, target, target_thndpn , t_renja_indikator_prog_keg_perubahan.id
				FROM t_renja_indikator_prog_keg_perubahan WHERE id_prog_keg=?";
			$result = $this->db->query($query, array($new_id, $row['id_renja']));

			# For kegiatan #
			$query="SELECT
					$ta AS tahun,
					t_renja_prog_keg_perubahan.id AS id_renja,
					is_prog_or_keg,
					kd_urusan,
					kd_bidang,
					kd_program,
					kd_kegiatan,
					nama_prog_or_keg,
					lokasi,
					lokasi AS lokasi_thndpn,
					penanggung_jawab,
					t_renja_prog_keg_perubahan.id_skpd,
					nominal,
					nominal_thndpn,
					catatan,
					catatan_thndpn,
					t_renja_prog_keg_perubahan.id_prog_rpjmd,
					? AS parent
				FROM t_renja_prog_keg_perubahan
				WHERE t_renja_prog_keg_perubahan.is_prog_or_keg=2 AND
					tahun=$ta AND t_renja_prog_keg_perubahan.parent=?
					AND t_renja_prog_keg_perubahan.id_skpd in (SELECT id_skpd FROM m_skpd WHERE kode_unit = ?)";
			$result = $this->db->query($query, array($new_id, $row['id_renja'], $id_skpd));
			$kegiatan_rka_baru = $result->result_array();

			foreach ($kegiatan_rka_baru as $row1) {
				$id_renja_nya = $row1['id_renja'];

				$this->db->insert("t_ppas_prog_keg_perubahan", $row1);
				$new_id = $this->db->insert_id();

				$query = "INSERT INTO t_ppas_indikator_prog_keg_perubahan(id_prog_keg, indikator, indikator_thndpn, satuan_target, satuan_target_thndpn, status_indikator, status_indikator_thndpn, kategori_indikator, kategori_indikator_thndpn, target, target_thndpn, id_indikator_renja)
											SELECT ?, indikator,indikator, satuan_target,satuan_target, status_indikator, status_indikator, kategori_indikator, kategori_indikator, target, target_thndpn, t_renja_indikator_prog_keg_perubahan.id
											FROM t_renja_indikator_prog_keg_perubahan WHERE id_prog_keg=?";
				$result = $this->db->query($query, array($new_id, $row1['id_renja']));



				//belanja kegiatan
				$query2 = "
							INSERT INTO `t_ppas_belanja_kegiatan_perubahan`
							(
							`id_renja`,
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
							`id_status_rka`,
							`id_keg`)
							SELECT
							 '$id_renja_nya',
							 tahun,
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
 							 '$new_id'
							FROM t_renja_perubahan_belanja_kegiatan
							WHERE  id_keg = '$id_renja_nya'
					 ";
					 $result2 =  $this->db->query($query2);
			}


		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function get_indikator_prog_keg($id, $return_result=TRUE, $satuan=FALSE){
		$this->db->select($this->table_indikator_program.".*, satuan_target as nama_value");
		$this->db->where('id_prog_keg', $id);
		$this->db->from($this->table_indikator_program);

		if ($satuan) {
			//$this->db->select("m_lov.nama_value");
			$this->db->select("m_status_indikator.nama_status_indikator");
			$this->db->select("m_kategori_indikator.nama_kategori_indikator");
			//$this->db->join("m_lov",$this->table_indikator_program.".satuan_target = m_lov.kode_value AND kode_app='1'","inner");
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

	function get_indikator_prog_keg_vdee($id, $return_result=TRUE, $satuan=FALSE){
		$this->db->select("*, satuan_target as nama_value");
		$this->db->where('id_prog_keg', $id);
		$this->db->where('target_thndpn > 0');
		$this->db->from('t_renja_indikator_prog_keg_perubahan');

		if ($satuan) {
			// $this->db->select("m_lov.nama_value");
			// $this->db->join("m_lov",$this->table_indikator_program.".satuan_target = m_lov.kode_value AND kode_app='1'","inner");
		}

		$result = $this->db->get();
		if ($return_result) {
			return $result->result();
		}else{
			return $result;
		}
	}

	function get_indikator_prog_keg_perubahan($id, $return_result=TRUE, $satuan=FALSE){
		$this->db->select($this->table_indikator_program_perubahan.".*, satuan_target as nama_value");
		$this->db->where('id_prog_keg', $id);
		$this->db->where('target_thndpn > 0');
		$this->db->from($this->table_indikator_program_perubahan);

		if ($satuan) {
			// $this->db->select("m_lov.nama_value");
			$this->db->select("m_status_indikator.nama_status_indikator");
			$this->db->select("m_kategori_indikator.nama_kategori_indikator");
			// $this->db->join("m_lov",$this->table_indikator_program_perubahan.".satuan_target = m_lov.kode_value AND kode_app='1'","inner");
			$this->db->join("m_status_indikator",$this->table_indikator_program_perubahan.".status_indikator = m_status_indikator.kode_status_indikator","inner");
			$this->db->join("m_kategori_indikator",$this->table_indikator_program_perubahan.".kategori_indikator = m_kategori_indikator.kode_kategori_indikator","inner");
		}

		$result = $this->db->get();
		if ($return_result) {
			return $result->result();
		}else{
			return $result;
		}
	}

	function get_all_kegiatan($id, $id_skpd, $ta){
		if ($this->session->userdata("id_skpd") > 100) {
			$id_skpd = $this->session->userdata("id_skpd");
			$query = "SELECT * FROM (`$this->table_program_kegiatan`)
			WHERE `id_skpd` in (SELECT id_skpd FROM m_asisten_sekda WHERE id_asisten = '$id_skpd')
			AND `tahun` = '$ta' AND parent = $id
			AND `is_prog_or_keg` = $this->is_kegiatan
			ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc, `kd_kegiatan` asc";

			$result = $this->db->query($query);
		}else {
			$cek = $this->m_skpd->get_kode_unit($id_skpd);
			if ($cek == $id_skpd) {
				$query = "SELECT * FROM (`$this->table_program_kegiatan`)
				WHERE `id_skpd` in (SELECT id_skpd FROM m_skpd WHERE kode_unit = '$id_skpd')
				AND `tahun` = '$ta' AND parent = $id
				AND `is_prog_or_keg` = $this->is_kegiatan
				ORDER BY `kd_urusan` asc, `kd_bidang` asc, `kd_program` asc, `kd_kegiatan` asc";

				$result = $this->db->query($query);
			}else {
				$this->db->select($this->table_program_kegiatan.".*");
				$this->db->where('id_skpd', $id_skpd);
				$this->db->where('tahun', $ta);
				$this->db->where('parent', $id);
				$this->db->where('is_prog_or_keg', $this->is_kegiatan);
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

	function add_program_skpd($data, $indikator, $satuan_target, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$add = array('is_prog_or_keg'=> $this->is_program);
		$data = $this->global_function->add_array($data, $add);

		$this->db->insert($this->table_program_kegiatan, $data);

		$id = $this->db->insert_id();
		foreach ($indikator as $key => $value) {
			$this->db->insert($this->table_indikator_program_perubahan, array('id_prog_keg' => $id, 'indikator' => $value,
			'satuan_target' => $satuan_target[$key], 'target' => $target[$key],'target_thndpn' => $target_thndpn[$key]));
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
				$this->db->update($this->table_indikator_program_perubahan, array('indikator' => $value, 'satuan_target' => $satuan_target[$key],
					'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
				unset($id_indikator_program[$key]);
			}else{
				$this->db->insert($this->table_indikator_program_perubahan, array('id_prog_keg' => $id_program, 'indikator' => $value,
				'satuan_target' => $satuan_target[$key],
				'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
			}
		}

		if (!empty($id_indikator_program)) {
			$this->db->where_in('id', $id_indikator_program);
			$this->db->delete($this->table_indikator_program_perubahan);
		}

		$renja = $this->get_one_program(NULL, NULL, $id_program);
		//$this->update_status_after_edit($renja->id, NULL, $id_program);

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

		function get_rka_belanja_per_tahun($id, $is_tahun){
		//------- query by deesudi
			$query = $this->db->query("SELECT id ,tahun,
							kode_sumber_dana AS kode_sumber_dana,(
								SELECT nama FROM m_sumberdana WHERE id_sumberdana = kode_sumber_dana
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
							FROM t_ppas_belanja_kegiatan_perubahan
							WHERE is_tahun_sekarang = '$is_tahun' AND id_keg = '$id'
							ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC");
		return $query->result();
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

	function add_kegiatan_skpd($data, $indikator, $satuan_target, $target, $target_thndpn){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$add = array('is_prog_or_keg'=> $this->is_kegiatan, 'id_status'=> $this->id_status_baru);
		$data = $this->global_function->add_array($data, $add);

		$this->db->insert($this->table_program_kegiatan, $data);

		$id = $this->db->insert_id();
		foreach ($indikator as $key => $value) {
			$this->db->insert($this->table_indikator_program_perubahan, array('id_prog_keg' => $id, 'indikator' => $value, 'satuan_target' => $satuan_target[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
		}

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function edit_kegiatan_skpd($data, $id_kegiatan, $indikator, $id_indikator_kegiatan, $satuan_target,$status_indikator, $kategori_indikator, $target, $target_thndpn,$dataKegiatan1, $dataKegiatan2){
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
				$this->db->update($this->table_indikator_program_perubahan, array('indikator' => $value, 'satuan_target' => $satuan_target[$key],'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],
				'status_indikator_thndpn' => $status_indikator[$key], 'kategori_indikator_thndpn' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
				unset($id_indikator_kegiatan[$key]);
			}else{
				$this->db->insert($this->table_indikator_program_perubahan, array('id_prog_keg' => $id_kegiatan, 'indikator' => $value, 'satuan_target' => $satuan_target[$key], 'status_indikator' => $status_indikator[$key], 'kategori_indikator' => $kategori_indikator[$key],
					 'status_indikator_thndpn' => $status_indikator[$key], 'kategori_indikator_thndpn' => $kategori_indikator[$key], 'target' => $target[$key], 'target_thndpn' => $target_thndpn[$key]));
			}
		}

		if (!empty($id_indikator_kegiatan)) {
			$this->db->where_in('id', $id_indikator_kegiatan);
			$this->db->delete($this->table_indikator_program_perubahan);
		}

		$KodeUrusan = $data['kd_urusan'];
		$KodeBidang = $data['kd_bidang'];
		$kodeProgram = $data['kd_program'];
		$KodeKegiatan = $data['kd_kegiatan'];
		$thnskr = $dataKegiatan1['tahun'];
		$thndpn = $dataKegiatan2['tahun'];
		$created_date =  date("d-m-Y_H-i-s");

		$this->db->query("delete from t_ppas_belanja_kegiatan_perubahan where id_keg = $id_kegiatan ");

		$banyakData1 = count($dataKegiatan1['kode_sumber_dana']);
		for($i =1; $i <= $banyakData1; ++$i) {
				$datatahun1_batch[] = array(
					'tahun'=>$thnskr,
					'kode_urusan'=>$KodeUrusan,
					'kode_bidang' => $KodeBidang,
					'kode_program' => $kodeProgram,
					'id_keg' => $id_kegiatan,
					'kode_kegiatan' => $KodeKegiatan,
					'kode_sumber_dana' => $dataKegiatan1['kode_sumber_dana'][$i],
					'kode_jenis_belanja' => $dataKegiatan1['kode_jenis_belanja'][$i],
					'kode_kategori_belanja' => $dataKegiatan1['kode_kategori_belanja'][$i],
					'kode_sub_kategori_belanja' => $dataKegiatan1['kode_sub_kategori_belanja'][$i],
					'kode_belanja' => $dataKegiatan1['kode_belanja'][$i],
					'uraian_belanja' => $dataKegiatan1['uraian_belanja'][$i],
					'detil_uraian_belanja' => $dataKegiatan1['detil_uraian_belanja'][$i],
					'volume' => $dataKegiatan1['volume'][$i],
					'nominal_satuan' => $dataKegiatan1['nominal_satuan'][$i],
					'satuan' => $dataKegiatan1['satuan'][$i],
					'subtotal' => $dataKegiatan1['subtotal'][$i],
					'is_tahun_sekarang'=>1,
					'id_status_rka'=>1,
					'created_date' => $created_date
					)	;
		}

		$banyakData2 = count($dataKegiatan2['kode_sumber_dana']);
		for($i =1; $i <= $banyakData2; ++$i) {
				$datatahun2_batch[] = array(
					'tahun'=>$thndpn,
					'kode_urusan'=>$KodeUrusan,
					'kode_bidang' => $KodeBidang,
					'kode_program' => $kodeProgram,
					'id_keg' => $id_kegiatan,
					'kode_kegiatan' => $KodeKegiatan,
					'kode_sumber_dana' => $dataKegiatan2['kode_sumber_dana'][$i],
					'kode_jenis_belanja' => $dataKegiatan2['kode_jenis_belanja'][$i],
					'kode_kategori_belanja' => $dataKegiatan2['kode_kategori_belanja'][$i],
					'kode_sub_kategori_belanja' => $dataKegiatan2['kode_sub_kategori_belanja'][$i],
					'kode_belanja' => $dataKegiatan2['kode_belanja'][$i],
					'uraian_belanja' => $dataKegiatan2['uraian_belanja'][$i],
					'detil_uraian_belanja' => $dataKegiatan2['detil_uraian_belanja'][$i],
					'volume' => $dataKegiatan2['volume'][$i],
					'nominal_satuan' => $dataKegiatan2['nominal_satuan'][$i],
					'satuan' => $dataKegiatan2['satuan'][$i],
					'subtotal' => $dataKegiatan2['subtotal'][$i],
					'is_tahun_sekarang'=>0,
					'id_status_rka'=>1,
					'created_date' => $created_date
					)	;
		}



		$this->db->insert_batch('t_ppas_belanja_kegiatan_perubahan', $datatahun1_batch);

		$this->db->insert_batch('t_ppas_belanja_kegiatan_perubahan', $datatahun2_batch);

		//$renstra = $this->get_one_kegiatan(NULL, NULL, NULL, $id_kegiatan);
		//$this->update_status_after_edit($renstra->id_renstra, NULL, NULL, $id_kegiatan);

		$this->db->trans_complete();
		return $this->db->trans_status();
	}

	function delete_kegiatan($id){
		$this->db->where('id', $id);
		$this->db->where('is_prog_or_keg', $this->is_kegiatan);
		$result = $this->db->delete($this->table_program_kegiatan);
		return $result;
	}

	function add_rka()
	{
		$data = $this->global_function->add_array($data, $add);

		$result = $this->db->insert($this->table_rka, $data);
		return $result;
	}

	function get_data($data,$table){
        $this->db->where($data);
        $query = $this->db->get($this->$table);
        return $query->row();
    }

	function get_rka_by_id($id_rka)
	{
		$sql = "
				SELECT *
				FROM t_rka
				WHERE id_rka = ?
			";

		$query = $this->db->query($sql, array($id_rka));

		if($query) {
			if($query->num_rows() > 0) {
				return $query->row();
			}
		}

		return NULL;
	}

	function simpan_rka($data_rka)
	{
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();


		$data_rka->created_date		= Formatting::get_datetime();
		$data_rka->created_by		= $this->session->userdata('username');

		$this->db->set($data_rka);
    	$this->db->insert('t_rka');

		$this->db->trans_complete();
		return $this->db->trans_status();
	}
    function update_rka($data,$id,$table,$primary) {
        $this->db->where($this->$primary,$id);
        return $this->db->update($this->$table,$data);
    }

	function get_data_table($search, $start, $length, $order)
	{
		$order_arr = array('id_rka','kd_urusan','kd_bidang','kd_program','kd_kegiatan');
		$sql = "
			SELECT * FROM (
				SELECT r.`id_rka`,r.`kd_urusan`,r.`kd_bidang`,r.`kd_program`,r.`kd_kegiatan`,r.`indikator_capaian`,r.`tahun_sekarang`,r.`lokasi`,
				r.`capaian_sekarang`,r.`jumlah_dana_sekarang`,r.`tahun_mendatang`,r.`capaian_mendatang`,r.`jumlah_dana_mendatang`,
				u.`Nm_Urusan` AS nm_urusan, b.`Nm_Bidang` AS nm_bidang, p.`Ket_Program` AS ket_program, k.`Ket_Kegiatan` AS ket_kegiatan
				FROM t_rka AS r
				LEFT JOIN m_urusan AS u ON r.`kd_urusan`=u.`Kd_Urusan`
				LEFT JOIN m_bidang AS b ON r.`kd_urusan`=b.`Kd_Urusan`
										AND r.`kd_bidang`=b.`Kd_Bidang`
				LEFT JOIN m_program AS p ON r.`kd_urusan`=p.`Kd_Urusan`
										AND r.`kd_bidang`=p.`Kd_Bidang`
										AND r.`kd_program`=p.`Kd_Prog`
				LEFT JOIN m_kegiatan AS k ON r.`kd_urusan`=k.`kd_urusan`
										AND r.`kd_bidang`=k.`Kd_Bidang`
										AND r.`kd_program`=k.`Kd_Prog`
										AND r.`kd_kegiatan`=k.`Kd_Keg`
				WHERE (r.kd_urusan LIKE '%".$search['value']."%'
				OR r.kd_bidang LIKE '%".$search['value']."%'
				OR r.kd_program LIKE '%".$search['value']."%'
				OR r.kd_kegiatan LIKE '%".$search['value']."%')
			) AS a
			order by ".$order_arr[$order["column"]]." ".$order["dir"]."
            limit $start,$length
		";
		// $sql="
		// 	SELECT * FROM (
		// 	SELECT r.id_rka
		// 	FROM ".$this->table_rka." AS r
		// 		LEFT JOIN
		// 	WHERE (kd_urusan LIKE '%".$search['value']."%'
  //           OR kd_bidang LIKE '%".$search['value']."%'
  //           OR kd_program LIKE '%".$search['value']."%'
  //           OR kd_kegiatan LIKE '%".$search['value']."%')
		// 	) AS a
		// ";
		//$this->db->limit($length, $start);
		//$this->db->order_by($order_arr[$order["column"]], $order["dir"]);

		$result = $this->db->query($sql);
		return $result->result();
	}

	function count_data_table($search, $start, $length, $order)
	{
		$this->db->from($this->table_rka);

		$this->db->like("kd_urusan", $search['value']);
		$this->db->or_like("kd_bidang", $search['value']);
		$this->db->or_like("kd_program", $search['value']);
		$this->db->or_like("kd_kegiatan", $search['value']);

		$result = $this->db->count_all_results();
		return $result;
	}

	function get_data_with_rincian($id_rka,$table)
	{
		$sql="
			SELECT * FROM ".$this->$table."
			WHERE id_rka = ?
		";

		$query = $this->db->query($sql, array($id_rka));

		if($query) {
				if($query->num_rows() > 0) {
					return $query->row();
				}
			}

			return NULL;
	}

    function delete_rka($id){
   	    $this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id_rka',$id);
        $this->db->delete('t_rka');


		$this->db->trans_complete();

		return $this->db->trans_status();
    }

	function get_urusan_skpd_4_cetak($id_skpd,$tahun)
    {
    	$query = "SELECT t.*,u.Nm_Urusan AS nama_urusan FROM (
			SELECT pro.*,
			SUM(keg.nomrka) AS sum_nomrka,
			SUM(keg.nomrka_perubahan) AS sum_nomrka_perubahan
			FROM
				(SELECT a.`id`,
					b.`id` AS id_perubahan,
					a.`tahun`,
					a.`kd_urusan`,
					a.`kd_bidang`,
					a.`kd_program`,
					a.`kd_kegiatan`,
					a.`nama_prog_or_keg`,
					a.`nominal` AS nomrka,
					b.`nominal` AS nomrka_perubahan,
					a.`id_skpd`,
					a.penanggung_jawab,
					a.lokasi,
					a.catatan,
					a.id_status,
					b.`penanggung_jawab` AS penanggung_jawab_perubahan,
					b.`lokasi` AS lokasi_perubahan ,
					b.`catatan` AS catatan_perubahan
				 FROM t_ppas_prog_keg a
				 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
							  AND a.`kd_bidang`=b.`kd_bidang`
							  AND a.`kd_program`=b.`kd_program`
							  AND a.`kd_kegiatan`=b.`kd_kegiatan`
							  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
							  AND a.`id_skpd`=b.`id_skpd`
							  AND a.`tahun`=b.`tahun`
				 WHERE a.is_prog_or_keg=1
				 GROUP BY a.`id`) AS pro
			INNER JOIN
				(SELECT a.`id`,
					b.`id` AS id_perubahan,
					a.`id_skpd`,
					a.`tahun`,
					a.`kd_urusan`,
					a.`kd_bidang`,
					a.`kd_program`,
					a.`kd_kegiatan`,
					a.`parent`,
					a.`nominal` AS nomrka,
					b.`nominal` AS nomrka_perubahan,
					b.`penanggung_jawab` AS penanggung_jawab_perubahan,
					b.`lokasi` AS lokasi_perubahan ,
					b.`catatan` AS catatan_perubahan
				 FROM t_ppas_prog_keg a
				 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
							  AND a.`kd_bidang`=b.`kd_bidang`
							  AND a.`kd_program`=b.`kd_program`
							  AND a.`kd_kegiatan`=b.`kd_kegiatan`
							  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
							  AND a.`id_skpd`=b.`id_skpd`
							  AND a.`tahun`=b.`tahun`
				 WHERE a.is_prog_or_keg=2
				 GROUP BY a.`kd_urusan`, a.`kd_bidang`, a.`kd_program`, a.`kd_kegiatan`,a.`id`) AS keg ON keg.parent=pro.id
			WHERE
				keg.id_skpd = ?
			AND keg.tahun = ?
			GROUP BY pro.kd_urusan
			ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC
		) t
		LEFT JOIN m_urusan AS u
		ON t.kd_urusan = u.Kd_Urusan;
		";

		$data = array($id_skpd,$tahun);
		$result = $this->db->query($query, $data);
		return $result->result();
    }

    function get_program_skpd_4_cetak($id_skpd,$tahun,$kd_urusan,$kd_bidang)
    {
    	$query = "SELECT pro.*,
				SUM(keg.nomrka) AS sum_nomrka,
				SUM(keg.nomrka_perubahan) AS sum_nomrka_perubahan
				FROM
					(SELECT a.`id`,
						b.`id` AS id_perubahan,
						a.`tahun`,
						a.`kd_urusan`,
						a.`kd_bidang`,
						a.`kd_program`,
						a.`kd_kegiatan`,
						a.`nama_prog_or_keg`,
						a.`nominal` AS nomrka,
						b.`nominal` AS nomrka_perubahan,
						a.`id_skpd`,
						a.penanggung_jawab,
						a.lokasi,
						a.catatan,
						a.id_status,
						b.`penanggung_jawab` AS penanggung_jawab_perubahan,
						b.`lokasi` AS lokasi_perubahan ,
						b.`catatan` AS catatan_perubahan
					 FROM t_ppas_prog_keg a
					 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
								  AND a.`kd_bidang`=b.`kd_bidang`
								  AND a.`kd_program`=b.`kd_program`
								  AND a.`kd_kegiatan`=b.`kd_kegiatan`
								  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
								  AND a.`id_skpd`=b.`id_skpd`
								  AND a.`tahun`=b.`tahun`
					 WHERE a.is_prog_or_keg=1
					 GROUP BY a.`id`) AS pro
				INNER JOIN
					(SELECT a.`id`,
						b.`id` AS id_perubahan,
						a.`id_skpd`,
						a.`tahun`,
						a.`kd_urusan`,
						a.`kd_bidang`,
						a.`kd_program`,
						a.`kd_kegiatan`,
						a.`parent`,
						a.`nominal` AS nomrka,
						b.`nominal` AS nomrka_perubahan,
						b.`penanggung_jawab` AS penanggung_jawab_perubahan,
						b.`lokasi` AS lokasi_perubahan ,
						b.`catatan` AS catatan_perubahan
					 FROM t_ppas_prog_keg a
					 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
								  AND a.`kd_bidang`=b.`kd_bidang`
								  AND a.`kd_program`=b.`kd_program`
								  AND a.`kd_kegiatan`=b.`kd_kegiatan`
								  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
								  AND a.`id_skpd`=b.`id_skpd`
								  AND a.`tahun`=b.`tahun`
					 WHERE a.is_prog_or_keg=2
					 GROUP BY a.`kd_urusan`, a.`kd_bidang`, a.`kd_program`, a.`kd_kegiatan`,a.`id`) AS keg ON keg.parent=pro.id
				WHERE
					keg.id_skpd = ?
					AND keg.tahun = ?
					AND keg.kd_urusan = ?
					AND keg.kd_bidang = ?
				GROUP BY pro.kd_program
				ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC";

		$data = array($id_skpd,$tahun,$kd_urusan,$kd_bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
    }

		function get_program_skpd_4_cetak_vdeeygatas($id_skpd,$tahun,$kd_urusan,$kd_bidang)
    {
    	$query = "
				SEECT pro.*,
				SUM(keg.nomrka) AS sum_nomrka,
				SUM(keg.nomrka_perubahan) AS sum_nomrka_perubahan
				FROM
					(SELECT a.`id`,
						b.`id` AS id_perubahan,
						a.`tahun`,
						a.`kd_urusan`,
						a.`kd_bidang`,
						a.`kd_program`,
						a.`kd_kegiatan`,
						a.`nama_prog_or_keg`,
						a.`nominal` AS nomrka,
						b.`nominal` AS nomrka_perubahan,
						a.`id_skpd`,
						a.penanggung_jawab,
						a.lokasi,
						a.catatan,
						a.id_status,
						b.`penanggung_jawab` AS penanggung_jawab_perubahan,
						b.`lokasi` AS lokasi_perubahan ,
						b.`catatan` AS catatan_perubahan
					 FROM t_ppas_prog_keg a
					 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
								  AND a.`kd_bidang`=b.`kd_bidang`
								  AND a.`kd_program`=b.`kd_program`
								  AND a.`kd_kegiatan`=b.`kd_kegiatan`
								  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
								  AND a.`id_skpd`=b.`id_skpd`
								  AND a.`tahun`=b.`tahun`
					 WHERE a.is_prog_or_keg=1
					 GROUP BY a.`id`) AS pro
				INNER JOIN
					(SELECT a.`id`,
						b.`id` AS id_perubahan,
						a.`id_skpd`,
						a.`tahun`,
						a.`kd_urusan`,
						a.`kd_bidang`,
						a.`kd_program`,
						a.`kd_kegiatan`,
						a.`parent`,
						a.`nominal` AS nomrka,
						b.`nominal` AS nomrka_perubahan,
						b.`penanggung_jawab` AS penanggung_jawab_perubahan,
						b.`lokasi` AS lokasi_perubahan ,
						b.`catatan` AS catatan_perubahan
					 FROM t_ppas_prog_keg a
					 LEFT JOIN t_ppas_prog_keg_perubahan b ON a.`kd_urusan`=b.`kd_urusan`
								  AND a.`kd_bidang`=b.`kd_bidang`
								  AND a.`kd_program`=b.`kd_program`
								  AND a.`kd_kegiatan`=b.`kd_kegiatan`
								  AND a.`is_prog_or_keg`=b.`is_prog_or_keg`
								  AND a.`id_skpd`=b.`id_skpd`
								  AND a.`tahun`=b.`tahun`
					 WHERE a.is_prog_or_keg=2
					 GROUP BY a.`kd_urusan`, a.`kd_bidang`, a.`kd_program`, a.`kd_kegiatan`,a.`id`) AS keg ON keg.parent=pro.id
				WHERE
					keg.id_skpd = ?
					AND keg.tahun = ?
					AND keg.kd_urusan = ?
					AND keg.kd_bidang = ?
				GROUP BY pro.kd_program
				ORDER BY kd_urusan ASC, kd_bidang ASC, kd_program ASC
				";
		$data = array($id_skpd,$tahun,$kd_urusan,$kd_bidang);
		$result = $this->db->query($query, $data);
		return $result->result();
    }

		function get_program_skpd_4_cetak_vdee($id_skpd,$tahun,$kd_urusan,$kd_bidang){
			//
			$query = "select * from (
						select r.*,p.Ket_Program as nama_prog_or_keg from (
						select
						r.*,
						sum(r.nomrka) AS sum_nomrka,
						sum(r.nomrka_perubahan) AS sum_nomrka_perubahan
						from (
						select
						k.*,
						r.id,
						r.penanggung_jawab,
						r.lokasi,
						r.catatan,
						r.id_status,
						r.`nominal` AS nomrka,
						rp.id_renja,
						rp.`penanggung_jawab` AS penanggung_jawab_perubahan,
						rp.`lokasi` AS lokasi_perubahan ,
						rp.`catatan` AS catatan_perubahan,
						rp.`nominal_thndpn` AS nomrka_perubahan
						from (
						select tahun,kd_urusan,kd_bidang,kd_program,kd_kegiatan,id_skpd from t_renja_prog_keg_perubahan where id_skpd = '".$id_skpd."' and tahun = '".$tahun."' and kd_kegiatan is not null
						union
						select tahun,kd_urusan,kd_bidang,kd_program,kd_kegiatan,id_skpd from t_ppas_prog_keg_perubahan where id_skpd = '".$id_skpd."' and tahun = '".$tahun."' and kd_kegiatan is not null
						) k
						left join t_renja_prog_keg_perubahan r
						on k.tahun = r.tahun
						and k.kd_urusan = r.kd_urusan
						and k.kd_bidang = r.kd_bidang
						and k.kd_program = r.kd_program
						and k.kd_kegiatan = r.kd_kegiatan
						and k.id_skpd = r.id_skpd
						left join t_ppas_prog_keg_perubahan as rp
						on k.tahun = rp.tahun
						and k.kd_urusan = rp.kd_urusan
						and k.kd_bidang = rp.kd_bidang
						and k.kd_program = rp.kd_program
						and k.kd_kegiatan = rp.kd_kegiatan
						and k.id_skpd = rp.id_skpd
						) r
						where kd_urusan = '".$kd_urusan."'
						and kd_bidang = '".$kd_bidang."'

						group by kd_program
						order by kd_urusan asc,kd_bidang asc,kd_program asc
						) r
						LEFT JOIN m_program AS p
						ON r.kd_urusan = p.Kd_Urusan and r.kd_bidang = p.Kd_Bidang and r.kd_program = p.Kd_Prog
						) AS kendali WHERE (kendali.sum_nomrka > 0 OR kendali.sum_nomrka_perubahan > 0)";
			//$data = array($id_skpd,$tahun,$kd_urusan,$kd_bidang);
			$result = $this->db->query($query);
			return $result->result();
		}

    function get_kegiatan_skpd_4_cetak($id_skpd,$tahun,$kd_urusan,$kd_bidang,$kd_program){
		$query = "SELECT
					r.*
					FROM (
					SELECT
					k.*,
					IF(r.nama_prog_or_keg='',r.nama_prog_or_keg,rp.nama_prog_or_keg) AS nama_prog_or_keg,
					r.id,
					r.penanggung_jawab,
					r.lokasi,
					r.catatan,
					r.id_status,
					r.`nominal` AS nomrka,
					rp.id AS id_perubahan,
					rp.id_renja,
					rp.`penanggung_jawab` AS penanggung_jawab_perubahan,
					rp.`lokasi` AS lokasi_perubahan ,
					rp.`catatan` AS catatan_perubahan,
					rp.`nominal_thndpn` AS nomrka_perubahan
					FROM (
					SELECT tahun,
						kd_urusan,
						kd_bidang,
						kd_program,
						kd_kegiatan,
						id_skpd FROM t_renja_prog_keg_perubahan
						WHERE id_skpd = ".$id_skpd."
						AND tahun = ".$tahun."
						AND kd_kegiatan IS NOT NULL
					UNION
					SELECT tahun,
						kd_urusan,
						kd_bidang,
						kd_program,
						kd_kegiatan,
						id_skpd FROM t_ppas_prog_keg_perubahan
						WHERE id_skpd = ".$id_skpd."
						AND tahun = ".$tahun."
						AND kd_kegiatan IS NOT NULL
					) k
					LEFT JOIN 
					(SELECT * FROM t_renja_prog_keg_perubahan WHERE id IN 
						(SELECT id_prog_keg FROM t_renja_indikator_prog_keg_perubahan WHERE target_thndpn > 0)) AS r
					ON k.tahun = r.tahun
					AND k.kd_urusan = r.kd_urusan
					AND k.kd_bidang = r.kd_bidang
					AND k.kd_program = r.kd_program
					AND k.kd_kegiatan = r.kd_kegiatan
					AND k.id_skpd = r.id_skpd
					LEFT JOIN 
					(SELECT * FROM t_ppas_prog_keg_perubahan WHERE id IN 
						(SELECT id_prog_keg FROM t_ppas_indikator_prog_keg_perubahan WHERE target_thndpn > 0)) AS rp
					ON k.tahun = rp.tahun
					AND k.kd_urusan = rp.kd_urusan
					AND k.kd_bidang = rp.kd_bidang
					AND k.kd_program = rp.kd_program
					AND k.kd_kegiatan = rp.kd_kegiatan
					AND k.id_skpd = rp.id_skpd
					) r
					WHERE kd_urusan = ".$kd_urusan."
					AND kd_bidang = ".$kd_bidang."
					AND kd_program = ".$kd_program."
					ORDER BY kd_urusan ASC,kd_bidang ASC,kd_program ASC,kd_kegiatan ASC";
		//$data = array($id_program);
		$result = $this->db->query($query);
		return $result;
	}

	function get_total_kegiatan_dan_indikator($id_program){
		$query = "SELECT
						COUNT(*) AS total
					FROM
						t_ppas_prog_keg_perubahan
					INNER JOIN
						t_ppas_indikator_prog_keg_perubahan ON t_ppas_indikator_prog_keg_perubahan.id_prog_keg=t_ppas_prog_keg_perubahan.id
					WHERE
						t_ppas_prog_keg_perubahan.parent=? OR t_ppas_prog_keg_perubahan.id=?";
		$data = array($id_program, $id_program);
		$result = $this->db->query($query, $data);
		return $result->row();
	}

	function get_one_rka_skpd($id_skpd, $detail=FALSE){
		$this->db->select($this->table.".*");
		$this->db->from($this->table);
		$this->db->where($this->table.".id_skpd", $id_skpd);

		if ($detail) {
			$this->db->select("nama_skpd");
			$this->db->join("m_skpd","t_ppas_perubahan.id_skpd = m_skpd.id_skpd","inner");
		}

		$result = $this->db->get();
		return $result->row();
	}

	function get_id_rka($id_skpd, $tahun, $kd_urusan, $kd_bidang, $kd_program){
		$query = "
						SELECT id
						FROM t_renja_prog_keg_perubahan
						WHERE id_skpd=?
						AND tahun=?
						AND kd_urusan = ?
						AND kd_bidang = ?
						AND kd_program = ?
						AND is_prog_or_keg =1
						";
			$data = array($id_skpd, $tahun,$kd_urusan,$kd_bidang,$kd_program);
			$result = $this->db->query($query, $data);
			if($result){
                $result = $result->row();
                return $result->id;
            }
            return 0;
	}

	function get_id_rka_perubahan($id_skpd, $tahun, $kd_urusan, $kd_bidang, $kd_program){
		$query = "
						SELECT id
						FROM t_ppas_prog_keg_perubahan
						WHERE id_skpd=?
						AND tahun=?
						AND kd_urusan = ?
						AND kd_bidang = ?
						AND kd_program = ?
						AND is_prog_or_keg =1
						";
			$data = array($id_skpd, $tahun,$kd_urusan,$kd_bidang,$kd_program);
			$result = $this->db->query($query, $data);
			if($result->row()!=NULL){
                $result = $result->row();
					return $result->id;
            }
            return 0;
	}


	function get_rka_belanja_per_tahun221($ta,$ta_tahun,$id_kegiatan){
		//------- query by deesudi


			$query = $this->db->query("SELECT id ,tahun,
							kode_sumber_dana AS kode_sumber_dana,(
								SELECT nama FROM m_sumberdana WHERE id_sumberdana = kode_sumber_dana
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
							SELECT id FROM m_tahun_anggaran WHERE tahun_anggaran = '$ta'
							) AS tahun_anggaran,(
							SELECT id FROM `t_ppas_prog_keg_perubahan` WHERE Kd_Urusan = kode_urusan  AND Kd_Bidang = kode_bidang AND kd_program = kode_program AND is_prog_or_keg ='1'
							) AS id_program,(
							SELECT nominal FROM `t_ppas_prog_keg_perubahan` WHERE id = '$id_kegiatan'
							) AS nominal_tahun,

							uraian_belanja,detil_uraian_belanja,volume,satuan,nominal_satuan,subtotal,tahun,id_keg , kode_urusan , kode_bidang , kode_program, kode_kegiatan
							FROM t_ppas_belanja_kegiatan_perubahan
							WHERE is_tahun_sekarang = '$ta' and tahun  = '$ta_tahun' and id_keg = '$id_kegiatan'
							ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC");
		return $query->result();
	}

function get_indikator_keluaran($ta, $idK){
			$query = $this->db->query("SELECT * FROM `t_ppas_indikator_prog_keg_perubahan` WHERE   `id_prog_keg` = '$idK'");
		return $query->result();
	}

	function get_indikator_capaian( $idP){
			$query = $this->db->query("SELECT * FROM `t_ppas_indikator_prog_keg_perubahan` WHERE   `id_prog_keg` = '$idP'");
		return $query->result();
	}
	function get_nominal_rka( $idK, $is_tahun_sekarang){
		// print_r($is_tahun_sekarang);
		// exit();
		if ($is_tahun_sekarang == '1') {
			$query = $this->db->query("SELECT  nominal_thndpn ,
									CASE WHEN
									(SELECT
										 nominal FROM t_ppas_prog_keg_perubahan WHERE  id = '$idK' AND tahun  =  tahun-1
									) IS NULL THEN 0 ELSE
									(SELECT
										 nominal FROM t_ppas_prog_keg_perubahan WHERE  id = '$idK' AND tahun  =  tahun-1
									) END AS nominal_min , nominal
									FROM `t_ppas_prog_keg_perubahan` WHERE id = '$idK' ");
		}else{
			$query = $this->db->query("SELECT  nominal_thndpn as nominal
									 , nominal as nominal_min , 0 as nominal_thndpn
									FROM `t_ppas_prog_keg_perubahan` WHERE id = '$idK' ");

		}
		return $query->result();
	}

	function get_all_ppas_veri(){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "SELECT kd_urusan AS kd, (SELECT nm_urusan FROM m_urusan WHERE m_urusan.kd_urusan = t_ppas_prog_keg_perubahan.kd_urusan) AS nama,
		SUM(nominal) AS nom, SUM(nominal_thndpn) AS nom_thndpn
		FROM t_ppas_prog_keg_perubahan
		WHERE  is_prog_or_keg = '2' AND tahun = ?
		GROUP BY kd_urusan, nama";
		$data = array($ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_all_bidang_ppas_veri($kd_urusan){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "SELECT kd_bidang AS kd,
		(SELECT nm_bidang FROM m_bidang WHERE m_bidang.kd_urusan = t_ppas_prog_keg_perubahan.kd_urusan AND m_bidang.kd_bidang = t_ppas_prog_keg_perubahan.kd_bidang) AS nama,
		SUM(nominal) AS nom, SUM(nominal_thndpn) AS nom_thndpn
		FROM t_ppas_prog_keg_perubahan
		WHERE kd_urusan = ? AND is_prog_or_keg = '2' AND tahun = ?
		GROUP BY kd_bidang, nama";
		$data = array($kd_urusan, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_all_skpd_ppas_veri($kd_urusan, $kd_bidang){
		$ta = $this->m_settings->get_tahun_anggaran();

		$query = "SELECT id_skpd AS kd,
		(SELECT nama_skpd FROM m_skpd WHERE m_skpd.id_skpd = t_ppas_prog_keg_perubahan.id_skpd) AS nama,
		SUM(nominal) AS nom, SUM(nominal_thndpn) AS nom_thndpn
		FROM t_ppas_prog_keg_perubahan
		WHERE kd_urusan = ? AND kd_bidang = ? AND is_prog_or_keg = '2' AND tahun = ?
		GROUP BY id_skpd, nama";
		$data = array($kd_urusan, $kd_bidang, $ta);
		$result = $this->db->query($query, $data);
		return $result->result();
	}

	function get_data_ppas($kd_urusan, $kd_bidang, $id_skpd){
		$ta = $this->m_settings->get_tahun_anggaran();

		//$query = "SELECT t_renja_prog_keg.* FROM t_renja_prog_keg INNER JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra INNER JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id WHERE t_renstra.id_skpd=? AND t_renja_prog_keg.tahun=? AND t_renja_prog_keg.id_status =? ORDER BY t_renja_prog_keg.kd_urusan, t_renja_prog_keg.kd_bidang, t_renja_prog_keg.kd_program, t_renja_prog_keg.kd_kegiatan";
		$query = "SELECT *, SUM(nominal) AS nom, SUM(nominal_thndpn) AS nom_thndpn
		FROM t_ppas_prog_keg_perubahan
		WHERE id_skpd = ? AND kd_urusan = ? AND kd_bidang = ? AND tahun = ?
		GROUP BY kd_program";
		$result = $this->db->query($query, array($id_skpd, $kd_urusan, $kd_bidang, $ta));
		return $result->result();
	}

	function get_belanja_per_tahun($id, $is_tahun){
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
							FROM t_ppas_belanja_kegiatan_perubahan
							WHERE is_tahun_sekarang = '$is_tahun' AND id_keg = '$id'
							ORDER BY kode_jenis_belanja ASC, kode_kategori_belanja ASC, kode_sub_kategori_belanja ASC, kode_belanja ASC");
		return $query->result();
	}

	// function disapprove_renja($ids, $idu, $idb, $ket){
	// 	$this->db->trans_strict(FALSE);
	// 	$this->db->trans_start();
	//
	// 	$query = "INSERT t_renja_revisi SELECT NULL,
	// 				t_renja_prog_keg.id, ?
	// 				FROM t_renja_prog_keg
	// 				LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
	// 				LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
	// 				WHERE t_renja_prog_keg.id_skpd=?";
	// 	$data = array($ket, $id);
	// 	$result = $this->db->query($query, $data);
	//
	// 	$query = "UPDATE t_renja_prog_keg
	// 				LEFT JOIN t_renstra_prog_keg ON t_renstra_prog_keg.id=t_renja_prog_keg.id_renstra
	// 				LEFT JOIN t_renstra ON t_renstra_prog_keg.id_renstra=t_renstra.id
	// 				SET t_renja_prog_keg.id_status=3
	// 				WHERE t_renja_prog_keg.id_skpd=?
	// 				AND t_renja_prog_keg.id_status=?";
	// 	$data = array($id, $this->id_status_send);
	// 	$result = $this->db->query($query, $data);
	//
	// 	$this->db->trans_complete();
	// 	return $this->db->trans_status();
	// }

	function get_one_history($kd){
		$query = "SELECT * FROM t_ppas_history_perubahan WHERE id_rka = '".$kd."' ORDER BY create_date DESC LIMIT 1";
		$result = $this->db->query($query);
		return $result->row();
	}


}
