<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class M_prioritas_pembangunan_rkpd extends CI_Model
{
	var $m_prioritas = 'm_prioritas_pembangunan';
	var $m_sasaran = 't_rpjmd_sasaran';
	var $m_program = 'm_rkpd_prog';
	var $m_kegiatan = 'm_rkpd_kegiatan';

	var $tx_prioritas = 'tx_rkpd_prioritas';
	var $tx_sasaran = 'tx_rkpd_prioritas_sasaran';
	var $tx_prog_keg = 'tx_rkpd_prioritas_prog_keg';
	var $tx_indikator_prog_keg = 'tx_rkpd_prioritas_indikator_prog_keg';
	var $tx_perangkat_daerah = 'tx_rkpd_prioritas_perangkat_daerah';

	//control
	function get_all_prioritas_combo(){
		return $this->db->get($this->m_prioritas);
	}

	function get_all_sasaran_combo(){
		return $this->db->get($this->m_sasaran);
	}

	function get_all_program_combo(){
		return $this->db->get($this->m_program);
	}

	function get_all_kegiatan_combo(){
		return $this->db->get($this->m_kegiatan);
	}


	//operasi
	function get_data_prioritas($tahun=NULL, $id=NULL){
		$this->db->select($this->tx_prioritas.'.id as id_prio, '.$this->tx_prioritas.'.*');
		// $this->db->select($this->tx_prioritas.'.id as id_prio, '.$this->m_prioritas.'.*,'.$this->tx_prioritas.'.*');
		// $this->db->join($this->m_prioritas, $this->tx_prioritas.'.id_prioritas = '.$this->m_prioritas.'.id', 'inner');
		if (!empty($tahun)) {
			$this->db->where($this->tx_prioritas.'.tahun', $tahun);
		}
		if (!empty($id)) {
			$this->db->where($this->tx_prioritas.'.id', $id);
		}
		$this->db->order_by($this->tx_prioritas.'.id', 'asc');
		return $this->db->get($this->tx_prioritas);
	}

	function add_prioritas($data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

	    $this->db->insert($this->tx_prioritas, $data);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function edit_prioritas($data, $id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->update($this->tx_prioritas, $data);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function delete_prioritas($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		// $this->db->where('id', $id);
		// $this->db->delete($this->tx_prioritas);
		// $this->db->where('id_rkpd_prioritas', $id);
		// $this->db->delete($this->tx_sasaran);
		// $this->db->where('id_rkpd_prioritas', $id);
		// $this->db->delete($this->tx_prog_keg);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function get_all_sasaran($id_prioritas){
		$this->db->select($this->tx_sasaran.'.id as id_prio, '.$this->m_sasaran.'.*,'.$this->tx_sasaran.'.*');
		$this->db->join($this->m_sasaran, $this->tx_sasaran.'.id_sasaran = '.$this->m_sasaran.'.id', 'left');
		$this->db->where($this->tx_sasaran.'.id_rkpd_prioritas', $id_prioritas);
		$this->db->order_by($this->tx_sasaran.'.id', 'asc');
		return $this->db->get($this->tx_sasaran);
	}

	function get_one_sasaran($id_sasaran){
		$this->db->select($this->tx_sasaran.'.id as id_prio, '.$this->m_sasaran.'.*,'.$this->tx_sasaran.'.*');
		$this->db->join($this->m_sasaran, $this->tx_sasaran.'.id_sasaran = '.$this->m_sasaran.'.id', 'left');
		$this->db->where($this->tx_sasaran.'.id', $id_sasaran);
		$this->db->order_by($this->tx_sasaran.'.id', 'asc');
		return $this->db->get($this->tx_sasaran);
	}

	function add_sasaran($data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

	    $this->db->insert($this->tx_sasaran, $data);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function edit_sasaran($data, $id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->update($this->tx_sasaran, $data);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function delete_sasaran($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		// $this->db->where('id', $id);
		// $this->db->delete($this->tx_sasaran);
		// $this->db->where('id_sasaran', $id);
		// $this->db->delete($this->tx_prog_keg);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function get_indikator_prog_keg($id_prog_or_keg){
		$this->db->select($this->tx_indikator_prog_keg.'.*, 
			m_status_indikator.nama_status_indikator as status_nya, 
			m_kategori_indikator.nama_kategori_indikator as kategori_nya');
		$this->db->join("m_status_indikator",$this->tx_indikator_prog_keg.".status_indikator = m_status_indikator.kode_status_indikator","inner");
		$this->db->join("m_kategori_indikator",$this->tx_indikator_prog_keg.".kategori_indikator = m_kategori_indikator.kode_kategori_indikator","inner");
		$this->db->where('id_rkpd_prog_keg', $id_prog_or_keg);
		return $this->db->get($this->tx_indikator_prog_keg);
	}

	function get_all_program($id_prioritas, $id_sasaran){
		$this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->tx_prog_keg.'.*');
		// $this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->m_program.'.*,'.$this->tx_prog_keg.'.*');
		// $this->db->join($this->m_program, $this->tx_prog_keg.'.id_prog_or_keg = '.$this->m_program.'.id', 'inner');
		$this->db->where($this->tx_prog_keg.'.id_rkpd_prioritas', $id_prioritas);
		$this->db->where($this->tx_prog_keg.'.id_rkpd_sasaran', $id_sasaran);
		$this->db->where($this->tx_prog_keg.'.is_prog_or_keg', '1');
		$this->db->order_by($this->tx_prog_keg.'.id', 'asc');
		return $this->db->get($this->tx_prog_keg);
	}

	function get_one_program($id_program){
		$this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->tx_prog_keg.'.*');
		// $this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->m_program.'.*,'.$this->tx_prog_keg.'.*');
		// $this->db->join($this->m_program, $this->tx_prog_keg.'.id_prog_or_keg = '.$this->m_program.'.id', 'inner');
		$this->db->where($this->tx_prog_keg.'.id', $id_program);
		$this->db->where($this->tx_prog_keg.'.is_prog_or_keg', '1');
		$this->db->order_by($this->tx_prog_keg.'.id', 'asc');
		return $this->db->get($this->tx_prog_keg);
	}

	function add_program($indikator_kinerja, $satuan_target, $status_target, $kategori_target, $target, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

	    $this->db->insert($this->tx_prog_keg, $data);
	    $id_prog_or_keg = $this->db->insert_id();

	    foreach ($indikator_kinerja as $key => $value) {
	    	$this->db->query("INSERT INTO ".$this->tx_indikator_prog_keg." SET
			indikator = '".$value."',
			satuan_target = '".$satuan_target[$key]."',
			status_indikator = '".$status_target[$key]."',
			kategori_indikator = '".$kategori_target[$key]."',
			target = '".$target[$key]."',
			id_rkpd_prog_keg = '".$id_prog_or_keg."'");
	    }

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function edit_program($id_indikator, $id, $indikator_kinerja, $satuan_target, $status_target, $kategori_target, $target, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->update($this->tx_prog_keg, $data);

	    foreach ($indikator_kinerja as $key => $value) {
	    	if (!empty($id_indikator[$key])) {
	    		$this->db->query("UPDATE ".$this->tx_indikator_prog_keg." SET
				indikator = '".$value."',
				satuan_target = '".$satuan_target[$key]."',
				status_indikator = '".$status_target[$key]."',
				kategori_indikator = '".$kategori_target[$key]."',
				target = '".$target[$key]."'
				WHERE 
				id_rkpd_prog_keg = '".$id."' AND id = '".$id_indikator[$key]."'");
	    		unset($id_indikator[$key]);
	    	}else{
	    		$this->db->query("INSERT INTO ".$this->tx_indikator_prog_keg." SET
				indikator = '".$value."',
				satuan_target = '".$satuan_target[$key]."',
				status_indikator = '".$status_target[$key]."',
				kategori_indikator = '".$kategori_target[$key]."',
				target = '".$target[$key]."',
				id_rkpd_prog_keg = '".$id."'");
	    	}
	    }

	    if (!empty($id_indikator)) {
	    	$this->db->where_in('id', $id_indikator);
	    	$this->db->delete($this->tx_indikator_prog_keg);
	    }

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function delete_program($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->delete($this->tx_prog_keg);
		$this->db->where('parent', $id);
		$this->db->delete($this->tx_prog_keg);
		$this->db->where('id_rkpd_prog_keg', $id);
		$this->db->delete($this->tx_indikator_prog_keg);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function get_all_perangkat_daerah($id_kegiatan, $for_join=FALSE){
		$this->db->where('id_prog_keg', $id_kegiatan);
		if ($for_join) {
			$this->db->join('m_skpd', $this->tx_perangkat_daerah.'.id_skpd = m_skpd.id_skpd', 'inner');
		}
		return $this->db->get($this->tx_perangkat_daerah);
	}

	function get_all_kegiatan($id_prioritas, $id_sasaran, $id_program){
		$this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->tx_prog_keg.'.*');
		// $this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->m_kegiatan.'.*,'.$this->tx_prog_keg.'.*');
		// $this->db->join($this->m_kegiatan, $this->tx_prog_keg.'.id_prog_or_keg = '.$this->m_kegiatan.'.id', 'inner');
		$this->db->where($this->tx_prog_keg.'.id_rkpd_prioritas', $id_prioritas);
		if (!empty($id_sasaran)) {
			$this->db->where($this->tx_prog_keg.'.id_rkpd_sasaran', $id_sasaran);
		}
		if (!empty($id_program)) {
			$this->db->where($this->tx_prog_keg.'.parent', $id_program);
		}
		$this->db->where($this->tx_prog_keg.'.is_prog_or_keg', '2');
		$this->db->order_by($this->tx_prog_keg.'.id', 'asc');
		return $this->db->get($this->tx_prog_keg);
	}

	function get_one_kegiatan($id_kegiatan){
		$this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->tx_prog_keg.'.*');
		// $this->db->select($this->tx_prog_keg.'.id as id_prio, '.$this->m_kegiatan.'.*,'.$this->tx_prog_keg.'.*');
		// $this->db->join($this->m_kegiatan, $this->tx_prog_keg.'.id_prog_or_keg = '.$this->m_kegiatan.'.id', 'inner');
		$this->db->where($this->tx_prog_keg.'.id', $id_kegiatan);
		$this->db->where($this->tx_prog_keg.'.is_prog_or_keg', '2');
		$this->db->order_by($this->tx_prog_keg.'.id', 'asc');
		return $this->db->get($this->tx_prog_keg);
	}

	function add_kegiatan($indikator_kinerja, $satuan_target, $status_target, $kategori_target, $target, $skpd, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

	    $this->db->insert($this->tx_prog_keg, $data);
	    $id_prog_or_keg = $this->db->insert_id();

	    foreach ($indikator_kinerja as $key => $value) {
	    	$this->db->query("INSERT INTO ".$this->tx_indikator_prog_keg." SET
			indikator = '".$value."',
			satuan_target = '".$satuan_target[$key]."',
			status_indikator = '".$status_target[$key]."',
			kategori_indikator = '".$kategori_target[$key]."',
			target = '".$target[$key]."',
			id_rkpd_prog_keg = '".$id_prog_or_keg."'");
	    }

	    foreach ($skpd as $key_skpd => $value_skpd) {
	    	$this->db->query("INSERT INTO ".$this->tx_perangkat_daerah." SET 
	    	id_skpd = '".$value_skpd."',
	    	id_prog_keg = '".$id_prog_or_keg."'");
	    }

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function edit_kegiatan($id_indikator, $id_skpd, $id, $indikator_kinerja, $satuan_target, $status_target, $kategori_target, $target, $skpd, $data){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->update($this->tx_prog_keg, $data);

	    foreach ($indikator_kinerja as $key => $value) {
	    	if (!empty($id_indikator[$key])) {
	    		$this->db->query("UPDATE ".$this->tx_indikator_prog_keg." SET
				indikator = '".$value."',
				satuan_target = '".$satuan_target[$key]."',
				status_indikator = '".$status_target[$key]."',
				kategori_indikator = '".$kategori_target[$key]."',
				target = '".$target[$key]."'
				WHERE 
				id_rkpd_prog_keg = '".$id."' AND id = '".$id_indikator[$key]."'");
	    		unset($id_indikator[$key]);
	    	}else{
	    		$this->db->query("INSERT INTO ".$this->tx_indikator_prog_keg." SET
				indikator = '".$value."',
				satuan_target = '".$satuan_target[$key]."',
				status_indikator = '".$status_target[$key]."',
				kategori_indikator = '".$kategori_target[$key]."',
				target = '".$target[$key]."',
				id_rkpd_prog_keg = '".$id."'");
	    	}
	    }

	    if (!empty($id_indikator)) {
	    	$this->db->where_in('id', $id_indikator);
	    	$this->db->delete($this->tx_indikator_prog_keg);
	    }

	    $this->db->where('id_prog_keg', $id);
	    $this->db->delete($this->tx_perangkat_daerah);
	    foreach ($skpd as $key_skpd => $value_skpd) {
	    	$this->db->query("INSERT INTO ".$this->tx_perangkat_daerah." SET 
	    	id_skpd = '".$value_skpd."',
	    	id_prog_keg = '".$id."'");
	    }

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function delete_kegiatan($id){
		$this->db->trans_strict(FALSE);
		$this->db->trans_start();

		$this->db->where('id', $id);
		$this->db->delete($this->tx_prog_keg);
		$this->db->where('id_rkpd_prog_keg', $id);
		$this->db->delete($this->tx_indikator_prog_keg);

	    $this->db->trans_complete();
	    return $this->db->trans_status();
	}

	function get_prog_keg_by_prioritas($id_prioritas, $is_prog_or_keg){
		$result = $this->db->query('SELECT * FROM t_renja_prog_keg 
			WHERE id_prioritas_daerah = "'.$id_prioritas.'" 
			AND is_prog_or_keg = "'.$is_prog_or_keg.'"
			GROUP BY kd_urusan, kd_bidang, kd_program, kd_kegiatan');
		return $result;
	}

	function get_skpd_by_prioritas($id_prioritas){
		$result = $this->db->query('SELECT b.* FROM t_renja_prog_keg a
			INNER JOIN m_skpd b
			ON a.id_skpd = b.id_skpd 
			WHERE id_prioritas_daerah = "'.$id_prioritas.'"
			GROUP BY a.id_skpd');
		return $result;

	}

	function get_prog_prioritas_by_skpd($id_skpd, $tahun, $is_prog_or_keg, $parent=NULL){
		if ($is_prog_or_keg == 1) {
			// $query = "SELECT * FROM ".$this->tx_prog_keg." WHERE id IN ( SELECT parent FROM ".$this->tx_prog_keg." INNER JOIN ".$this->tx_perangkat_daerah." ON ".$this->tx_prog_keg.".id = ".$this->tx_perangkat_daerah.".id_prog_keg WHERE id_skpd = '".$id_skpd."' ) AND is_prog_or_keg = 1 AND tahun = '".$tahun."'";
			$query = "SELECT * FROM ".$this->tx_prog_keg." WHERE is_prog_or_keg = 1 AND tahun = '".$tahun."'";
			$result = $this->db->query($query);
		}else{
			// $query = "SELECT * FROM ".$this->tx_prog_keg." INNER JOIN ".$this->tx_perangkat_daerah." ON ".$this->tx_prog_keg.".id = ".$this->tx_perangkat_daerah.".id_prog_keg WHERE id_skpd = '".$id_skpd."' AND is_prog_or_keg = 2 AND tahun = '".$tahun."'";
			$query = "SELECT * FROM ".$this->tx_prog_keg." WHERE is_prog_or_keg = 2 AND tahun = '".$tahun."' AND parent = '".$parent."'";
			$result = $this->db->query($query);
		}


		return $result;
	}
}
