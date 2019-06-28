<?php class Database_model extends CI_Model {

	private $engine = 'InnoDB';
	/* define versi opensid dan script migrasi yang harus dijalankan */
	private $versionMigrate = array(
		'2.4' => array('migrate' => 'migrasi_24_ke_25','nextVersion' => '2.5'),
		'pra-2.5' => array('migrate' => 'migrasi_24_ke_25','nextVersion' => '2.5'),
		'2.5' => array('migrate' => 'migrasi_25_ke_26', 'nextVersion' => '2.6'),
		'2.6' => array('migrate' => 'migrasi_26_ke_27', 'nextVersion' => '2.7'),
		'2.7' => array('migrate' => 'migrasi_27_ke_28', 'nextVersion' => '2.8'),
		'2.8' => array('migrate' => 'migrasi_28_ke_29', 'nextVersion' => '2.9'),
		'2.9' => array('migrate' => 'migrasi_29_ke_210', 'nextVersion' => '2.10'),
		'2.10' => array('migrate' => 'migrasi_210_ke_211', 'nextVersion' => '2.11'),
		'2.11' => array('migrate' => 'migrasi_211_ke_1806', 'nextVersion' => '18.06'),
		'2.12' => array('migrate' => 'migrasi_211_ke_1806', 'nextVersion' => '18.06'),
		'18.06' => array('migrate' => 'migrasi_1806_ke_1807', 'nextVersion' => '18.08'),
		'18.07' => array('migrate' => 'migrasi_1806_ke_1807', 'nextVersion' => '18.08'),
		'18.08' => array('migrate' => 'migrasi_1808_ke_1809', 'nextVersion' => '18.09'),
		'18.09' => array('migrate' => 'migrasi_1809_ke_1810', 'nextVersion' => '18.10'),
		'18.10' => array('migrate' => 'migrasi_1810_ke_1811', 'nextVersion' => '18.11'),
		'18.11' => array('migrate' => 'migrasi_1811_ke_1812', 'nextVersion' => '18.12'),
		'18.12' => array('migrate' => 'migrasi_1812_ke_1901', 'nextVersion' => '19.01'),
		'19.01' => array('migrate' => 'migrasi_1901_ke_1902', 'nextVersion' => '19.02'),
		'19.02' => array('migrate' => 'nop', 'nextVersion' => '19.03'),
		'19.03' => array('migrate' => 'migrasi_1903_ke_1904', 'nextVersion' => '19.04'),
		'19.04' => array('migrate' => 'migrasi_1904_ke_1905', 'nextVersion' => '19.05'),
		'19.05' => array('migrate' => 'migrasi_1905_ke_1906', 'nextVersion' => '19.06'),
		'19.06' => array('migrate' => 'migrasi_1906_ke_1907', 'nextVersion' => NULL)
	);

	public function __construct()
	{
		parent::__construct();

		$this->cek_engine_db();
		$this->load->dbforge();
		$this->load->model('folder_desa_model');
		$this->load->model('surat_master_model');
		$this->load->model('analisis_import_model');
	}

	private function cek_engine_db()
	{
		$this->db->db_debug = FALSE; //disable debugging for queries

			$query = $this->db->query("SELECT `engine` FROM INFORMATION_SCHEMA.TABLES WHERE table_schema= '". $this->db->database ."' AND table_name = 'user'");
			$error = $this->db->error();
			if ($error['code'] != 0)
			{
				$this->engine = $query->row()->engine;
			}

		$this->db->db_debug = $db_debug; //restore setting
	}

	private function reset_setting_aplikasi()
	{
		$this->db->truncate('setting_aplikasi');
		$query = "
			INSERT INTO setting_aplikasi (`id`, `key`, `value`, `keterangan`, `jenis`,`kategori`) VALUES
			(1, 'sebutan_kabupaten','kabupaten','Pengganti sebutan wilayah kabupaten','',''),
			(2, 'sebutan_kabupaten_singkat','kab.','Pengganti sebutan singkatan wilayah kabupaten','',''),
			(3, 'sebutan_kecamatan','kecamatan','Pengganti sebutan wilayah kecamatan','',''),
			(4, 'sebutan_kecamatan_singkat','kec.','Pengganti sebutan singkatan wilayah kecamatan','',''),
			(5, 'sebutan_desa','desa','Pengganti sebutan wilayah desa','',''),
			(6, 'sebutan_dusun','dusun','Pengganti sebutan wilayah dusun','',''),
			(7, 'sebutan_camat','camat','Pengganti sebutan jabatan camat','',''),
			(8, 'website_title','Website Resmi','Judul tab browser modul web','','web'),
			(9, 'login_title','OpenSID', 'Judul tab browser halaman login modul administrasi','',''),
			(10, 'admin_title','Sistem Informasi Desa','Judul tab browser modul administrasi','',''),
			(11, 'web_theme', 'default','Tema penampilan modul web','','web'),
			(12, 'offline_mode',FALSE,'Apakah modul web akan ditampilkan atau tidak','boolean',''),
			(13, 'enable_track',TRUE,'Apakah akan mengirimkan data statistik ke tracker','boolean',''),
			(14, 'dev_tracker','','Host untuk tracker pada development','','development'),
			(15, 'nomor_terakhir_semua_surat', FALSE,'Gunakan nomor surat terakhir untuk seluruh surat tidak per jenis surat','boolean',''),
			(16, 'google_key','','Google API Key untuk Google Maps','','web'),
			(17, 'libreoffice_path','','Path tempat instal libreoffice di server SID','','')
		";
		$this->db->query($query);
	}

	public function migrasi_db_cri()
	{
		$versi = $this->getCurrentVersion();
		$nextVersion = $versi;
		$versionMigrate = $this->versionMigrate;
		if (isset($versionMigrate[$versi]))
		{
			while (!empty($nextVersion) AND !empty($versionMigrate[$nextVersion]['migrate']))
			{
				$migrate = $versionMigrate[$nextVersion]['migrate'];
				log_message('error', 'Jalankan '.$migrate);
				$nextVersion = $versionMigrate[$nextVersion]['nextVersion'];
				call_user_func(__NAMESPACE__ .'\Database_model::'.$migrate);
			}
		}
		else
		{
			$this->_migrasi_db_cri();
		}
		$this->folder_desa_model->amankan_folder_desa();
		$this->surat_master_model->impor_surat_desa();
		$this->db->where('id', 13)->update('setting_aplikasi', array('value' => TRUE));
		/*
			Update current_version di db.
			'pasca-<versi>' atau '<versi>-pasca disimpan sebagai '<versi>'
		*/
		$versi = AmbilVersi();
		$versi = preg_replace('/pasca-|-pasca/', '', $versi);
		$newVersion = array(
			'value' => $versi
		);
		$this->db->where(array('key'=>'current_version'))->update('setting_aplikasi', $newVersion);
		$this->load->model('track_model');
		$this->track_model->kirim_data();
	 	$_SESSION['success'] = 1;
  }

  private function getCurrentVersion()
  {
	// Untuk kasus tabel setting_aplikasi belum ada
	if (!$this->db->table_exists('setting_aplikasi')) return NULL;
	$result = NULL;
	$_result = $this->db->where(array('key' => 'current_version'))->get('setting_aplikasi')->row();
	if (!empty($_result))
	{
	  $result = $_result->value;
	}
	return $result;
  }

  private function nop()
  {
  	// Tidak lakukan apa-apa
  }

  private function _migrasi_db_cri()
  {
	$this->migrasi_cri_lama();
	$this->migrasi_03_ke_04();
	$this->migrasi_08_ke_081();
	$this->migrasi_082_ke_09();
	$this->migrasi_092_ke_010();
	$this->migrasi_010_ke_10();
	$this->migrasi_10_ke_11();
	$this->migrasi_111_ke_12();
	$this->migrasi_124_ke_13();
	$this->migrasi_13_ke_14();
	$this->migrasi_14_ke_15();
	$this->migrasi_15_ke_16();
	$this->migrasi_16_ke_17();
	$this->migrasi_17_ke_18();
	$this->migrasi_18_ke_19();
	$this->migrasi_19_ke_110();
	$this->migrasi_110_ke_111();
	$this->migrasi_111_ke_112();
	$this->migrasi_112_ke_113();
	$this->migrasi_113_ke_114();
	$this->migrasi_114_ke_115();
	$this->migrasi_115_ke_116();
	$this->migrasi_116_ke_117();
	$this->migrasi_117_ke_20();
	$this->migrasi_20_ke_21();
	$this->migrasi_21_ke_22();
	$this->migrasi_22_ke_23();
	$this->migrasi_23_ke_24();
	$this->migrasi_24_ke_25();
	$this->migrasi_25_ke_26();
	$this->migrasi_26_ke_27();
	$this->migrasi_27_ke_28();
	$this->migrasi_28_ke_29();
	$this->migrasi_29_ke_210();
	$this->migrasi_210_ke_211();
	$this->migrasi_211_ke_1806();
	$this->migrasi_1806_ke_1807();
	$this->migrasi_1808_ke_1809();
	$this->migrasi_1809_ke_1810();
	$this->migrasi_1810_ke_1811();
	$this->migrasi_1811_ke_1812();
	$this->migrasi_1812_ke_1901();
	$this->migrasi_1901_ke_1902();
	$this->migrasi_1903_ke_1904();
	$this->migrasi_1904_ke_1905();
	$this->migrasi_1905_ke_1906();
	$this->migrasi_1906_ke_1907();
  }

  private function migrasi_1906_ke_1907()
  {
	// Menambahkan Tabel tweb_aset yang digunakan unhtuk autofield pada pemilihan aset
	if (!$this->db->table_exists('tweb_aset'))
	{
		$query = "
			CREATE TABLE `tweb_aset` (
				`id_aset` int(11) NOT NULL,
				`golongan` varchar(11) NOT NULL,
				`bidang` varchar(11) NOT NULL,
				`kelompok` varchar(11) NOT NULL,
				`sub_kelompok` varchar(11) NOT NULL,
				`sub_sub_kelompok` varchar(11) NOT NULL,
				`nama` varchar(255) NOT NULL,
				PRIMARY KEY (id_aset)
			)
				";

		$this->db->query($query);

		$this->db->truncate('tweb_aset');
		$query = "
			INSERT INTO tweb_aset (`id_aset`, `golongan`, `bidang`, `kelompok`, `sub_kelompok`, `sub_sub_kelompok`, `nama`) VALUES
			(1, '1', '00', '00', '00', '000', 'TANAH'),
			(2, '1', '01', '00', '00', '000', 'TANAH DESA'),
			(3, '1', '01', '01', '00', '000', 'TANAH KAS DESA'),
			(4, '1', '01', '01', '01', '000', 'TANAH BENGKOK'),
			(5, '1', '01', '01', '01', '001', 'TANAH BENGKOK KEPALA DESA'),
			(6, '1', '01', '01', '01', '999', 'TANAH BENGKOK LAINNYA'),
			(7, '1', '01', '01', '02', '000', 'TANAH BONDO'),
			(8, '1', '01', '01', '03', '000', 'TANAH KALAKERAN NEGERI'),
			(9, '1', '01', '01', '04', '000', 'TANAH PECATU'),
			(10, '1', '01', '01', '05', '000', 'TANAH PENGAREM-AREM'),
			(11, '1', '01', '01', '06', '000', 'TANAH TITISARA'),
			(12, '1', '01', '02', '00', '000', 'TANAH PERKAMPUNGAN'),
			(13, '1', '01', '02', '01', '000', 'TANAH PERKAMPUNGAN'),
			(14, '1', '01', '02', '01', '001', 'TANAH PERKAMPUNGAN'),
			(15, '1', '01', '02', '01', '999', 'TANAH PERKAMPUNGAN LAINNYA'),
			(16, '1', '01', '02', '02', '000', 'EMPLASMEN'),
			(17, '1', '01', '02', '02', '001', 'EMPLASMEN'),
			(18, '1', '01', '02', '02', '999', 'EMPLASMEN LAINNYA'),
			(19, '1', '01', '02', '03', '000', 'TANAH KUBURAN'),
			(20, '1', '01', '02', '03', '001', 'TANAH KUBURAN ISLAM'),
			(21, '1', '01', '02', '03', '002', 'TANAH KUBURAN KRISTEN'),
			(22, '1', '01', '02', '03', '003', 'TANAH KUBURAN CINA'),
			(23, '1', '01', '02', '03', '004', 'TANAH KUBURAN HINDU'),
			(24, '1', '01', '02', '03', '005', 'TANAH KUBURAN BUDHA'),
			(25, '1', '01', '02', '03', '006', 'TANAH MAKAM PAHLAWAN'),
			(26, '1', '01', '02', '03', '007', 'TANAH KUBURAN TEMPAT BENDA BERSEJARAH'),
			(27, '1', '01', '02', '03', '008', 'TANAH MAKAM UMUM/KUBURAN UMUM'),
			(28, '1', '01', '02', '03', '999', 'TANAH KUBURAN LAINNYA'),
			(29, '1', '01', '03', '00', '000', 'TANAH PERTANIAN'),
			(30, '1', '01', '03', '01', '000', 'SAWAH SATU TAHUN DITANAMI'),
			(31, '1', '01', '03', '01', '001', 'SAWAH DITANAMI PADI'),
			(32, '1', '01', '03', '01', '002', 'SAWAH DITANAMI PALAWIJA'),
			(33, '1', '01', '03', '01', '003', 'SAWAH DITANAMI TEBU'),
			(34, '1', '01', '03', '01', '004', 'SAWAH DITANAMI SAYURAN'),
			(35, '1', '01', '03', '01', '005', 'SAWAH DITANAMI TEMBAKAU'),
			(36, '1', '01', '03', '01', '006', 'SAWAH DITANAMI ROSELLA'),
			(37, '1', '01', '03', '01', '999', 'SAWAH DITANAMI LAINNYA'),
			(38, '1', '01', '03', '02', '000', 'TANAH KERING/TEGALAN'),
			(39, '1', '01', '03', '02', '001', 'TANAH KERING DITANAMI BUAH-BUAHAN'),
			(40, '1', '01', '03', '02', '002', 'TANAH KERING DITANAMI TEMBAKAU'),
			(41, '1', '01', '03', '02', '003', 'TANAH KERING DITANAMI JAGUNG'),
			(42, '1', '01', '03', '02', '004', 'TANAH KERING DITANAMI KETELA POHON'),
			(43, '1', '01', '03', '02', '005', 'TANAH KERING DITANAMI KACANG TANAH'),
			(44, '1', '01', '03', '02', '006', 'TANAH KERING DITANAMI KACANG HIJAU'),
			(45, '1', '01', '03', '02', '007', 'TANAH KERING DITANAMI KEDELAI'),
			(46, '1', '01', '03', '02', '008', 'TANAH KERING DITANAMI UBI JALAR'),
			(47, '1', '01', '03', '02', '009', 'TANAH KERING DITANAMI KELADI'),
			(48, '1', '01', '03', '02', '999', 'TANAH KERING DITANAMI LAINNYA'),
			(49, '1', '01', '03', '03', '000', 'LADANG'),
			(50, '1', '01', '03', '03', '001', 'LADANG PADI'),
			(51, '1', '01', '03', '03', '002', 'LADANG JAGUNG'),
			(52, '1', '01', '03', '03', '003', 'LADANG KETELA POHON'),
			(53, '1', '01', '03', '03', '004', 'LADANG KACANG TANAH'),
			(54, '1', '01', '03', '03', '005', 'LADANG KACANG HIJAU'),
			(55, '1', '01', '03', '03', '006', 'LADANG KEDELAI'),
			(56, '1', '01', '03', '03', '007', 'LADANG UBI JALAR'),
			(57, '1', '01', '03', '03', '008', 'LADANG KELADI'),
			(58, '1', '01', '03', '03', '009', 'LADANG BENGKUANG'),
			(59, '1', '01', '03', '03', '010', 'LADANG APEL'),
			(60, '1', '01', '03', '03', '011', 'LADANG KENTANG'),
			(61, '1', '01', '03', '03', '012', 'LADANG JERUK'),
			(62, '1', '01', '03', '03', '999', 'LADANG LAINNYA'),
			(63, '1', '01', '04', '00', '000', 'TANAH PERKEBUNAN'),
			(64, '1', '01', '04', '01', '000', 'TANAH PERKEBUNAN'),
			(65, '1', '01', '04', '01', '001', 'TANAH PERKEBUNAN KARET'),
			(66, '1', '01', '04', '01', '002', 'TANAH PERKEBUNAN KOPI'),
			(67, '1', '01', '04', '01', '003', 'TANAH PERKEBUNAN KELAPA'),
			(68, '1', '01', '04', '01', '004', 'TANAH PERKEBUNAN RANDU'),
			(69, '1', '01', '04', '01', '005', 'TANAH PERKEBUNAN LADA'),
			(70, '1', '01', '04', '01', '006', 'TANAH PERKEBUNAN TEH'),
			(71, '1', '01', '04', '01', '007', 'TANAH PERKEBUNAN KINA'),
			(72, '1', '01', '04', '01', '008', 'TANAH PERKEBUNAN COKLAT'),
			(73, '1', '01', '04', '01', '009', 'TANAH PERKEBUNAN KELAPA SAWIT'),
			(74, '1', '01', '04', '01', '010', 'TANAH PERKEBUNAN SEREH'),
			(75, '1', '01', '04', '01', '011', 'TANAH PERKEBUNAN CENGKEH'),
			(76, '1', '01', '04', '01', '012', 'TANAH PERKEBUNAN PALA'),
			(77, '1', '01', '04', '01', '013', 'TANAH PERKEBUNAN SAGU'),
			(78, '1', '01', '04', '01', '014', 'TANAH PERKEBUNAN JAMBU MENTE'),
			(79, '1', '01', '04', '01', '015', 'TANAH PERKEBUNAN TENGKAWANG'),
			(80, '1', '01', '04', '01', '016', 'TANAH PERKEBUNAN MINYAK KAYU PUTIH'),
			(81, '1', '01', '04', '01', '017', 'TANAH PERKEBUNAN KAYU MANIS'),
			(82, '1', '01', '04', '01', '018', 'TANAH PERKEBUNAN PETAI'),
			(83, '1', '01', '04', '01', '999', 'TANAH PERKEBUNAN LAINNYA'),
			(84, '1', '01', '05', '00', '000', 'TANAH HUTAN'),
			(85, '1', '01', '05', '01', '000', 'TANAH HUTAN LEBAT (DITANAMI JENIS KAYU UTAMA)'),
			(86, '1', '01', '05', '01', '001', 'TANAH HUTAN MERANTI'),
			(87, '1', '01', '05', '01', '002', 'TANAH HUTAN RASAMALA'),
			(88, '1', '01', '05', '01', '003', 'TANAH HUTAN BULIAN'),
			(89, '1', '01', '05', '01', '004', 'TANAH HUTAN MEDANG'),
			(90, '1', '01', '05', '01', '005', 'TANAH HUTAN JELUTUNG'),
			(91, '1', '01', '05', '01', '006', 'TANAH HUTAN RAMIN'),
			(92, '1', '01', '05', '01', '007', 'TANAH HUTAN PUSPA'),
			(93, '1', '01', '05', '01', '008', 'TANAH HUTAN SUNINTEM'),
			(94, '1', '01', '05', '01', '009', 'TANAH HUTAN ALBENIA'),
			(95, '1', '01', '05', '01', '010', 'TANAH HUTAN KAYU BESI/ULIN'),
			(96, '1', '01', '05', '01', '999', 'HUTAN LEBAT LAINNYA'),
			(97, '1', '01', '05', '02', '000', 'TANAH HUTAN BELUKAR'),
			(98, '1', '01', '05', '02', '001', 'TANAH HUTAN SEMAK-SEMAK'),
			(99, '1', '01', '05', '02', '002', 'HUTAN BELUKAR'),
			(100, '1', '01', '05', '02', '003', 'HUTAN BELUKAR LAINNYA'),
			(101, '1', '01', '05', '03', '000', 'HUTAN TANAMAN JENIS'),
			(102, '1', '01', '05', '03', '001', 'HUTAN TANAMAN JATI'),
			(103, '1', '01', '05', '03', '002', 'HUTAN TANAMAN PINUS'),
			(104, '1', '01', '05', '03', '003', 'HUTAN TANAMAN ROTAN'),
			(105, '1', '01', '05', '03', '999', 'HUTAN TANAMAN JENIS LAINNYA'),
			(106, '1', '01', '05', '04', '000', 'HUTAN ALAM SEJENIS/HUTAN RAWA'),
			(107, '1', '01', '05', '04', '001', 'HUTAN BAKAU'),
			(108, '1', '01', '05', '04', '002', 'HUTAN CEMARA (YANG TIDAK DITANAMAN)'),
			(109, '1', '01', '05', '04', '003', 'HUTAN GALAM'),
			(110, '1', '01', '05', '04', '004', 'HUTAN NIPAH'),
			(111, '1', '01', '05', '04', '005', 'HUTAN BAMBU'),
			(112, '1', '01', '05', '04', '006', 'HUTAN ROTAN'),
			(113, '1', '01', '05', '04', '999', 'HUTAN ALAM SEJENIS LAINNYA'),
			(114, '1', '01', '05', '05', '000', 'HUTAN UNTUK PENGGUNAAN KHUSUS'),
			(115, '1', '01', '05', '05', '001', 'HUTAN CADANGAN'),
			(116, '1', '01', '05', '05', '002', 'HUTAN LINDUNG'),
			(117, '1', '01', '05', '05', '003', 'HUTAN CAGAR ALAM'),
			(118, '1', '01', '05', '05', '004', 'HUTAN TAMAN WISATA'),
			(119, '1', '01', '05', '05', '005', 'HUTAN TAMAN BURUNG'),
			(120, '1', '01', '05', '05', '006', 'HUTAN SUAKA MARGA SATWA'),
			(121, '1', '01', '05', '05', '007', 'HUTAN TAMAN NASIONAL'),
			(122, '1', '01', '05', '05', '008', 'HUTAN PRODUKSI'),
			(123, '1', '01', '05', '05', '999', 'HUTAN UNTUK PENGGUNAAN KHUSUS LAINNYA'),
			(124, '1', '01', '06', '00', '000', 'TANAH KEBUN CAMPURAN'),
			(125, '1', '01', '06', '01', '000', 'TANAH YANG TIDAK ADA JARINGAN PENGAIRAN'),
			(126, '1', '01', '06', '01', '001', 'TANAMAN RUPA-RUPA'),
			(127, '1', '01', '06', '01', '999', 'TANAH KEBUN CAMPURAN LAINNYA'),
			(128, '1', '01', '06', '02', '000', 'TUMBUH LIAR BERCAMPUR JENIS LAIN'),
			(129, '1', '01', '06', '02', '001', 'JENIS TANAMAN RUPA-RUPA & TIDAK JELAS MANA YANG MENONJOL'),
			(130, '1', '01', '06', '02', '002', 'TANAMAN LUAR PERKARANGAN'),
			(131, '1', '01', '06', '02', '999', 'TUMBUH LIAR BERCAMPUR JENIS LAINNYA'),
			(132, '1', '01', '07', '00', '000', 'TANAH KOLAM IKAN'),
			(133, '1', '01', '07', '01', '000', 'TAMBAK'),
			(134, '1', '01', '07', '01', '001', 'TAMBAK'),
			(135, '1', '01', '07', '01', '999', 'TAMBAK LAINNYA'),
			(136, '1', '01', '07', '02', '000', 'AIR TAWAR'),
			(137, '1', '01', '07', '02', '001', 'KOLAM AIR TAWAR'),
			(138, '1', '01', '07', '02', '999', 'AIR TAWAR LAINNYA'),
			(139, '1', '01', '08', '00', '000', 'TANAH DANAU / RAWA'),
			(140, '1', '01', '08', '01', '000', 'RAWA'),
			(141, '1', '01', '08', '01', '001', 'RAWA'),
			(142, '1', '01', '08', '01', '999', 'RAWA LAINNYA'),
			(143, '1', '01', '08', '02', '000', 'DANAU'),
			(144, '1', '01', '08', '02', '001', 'SANAU/SITU'),
			(145, '1', '01', '08', '02', '002', 'WADUK'),
			(146, '1', '01', '08', '02', '999', 'DANAU LAINNYA'),
			(147, '1', '01', '09', '00', '000', 'TANAH TANDUS / RUSAK'),
			(148, '1', '01', '09', '01', '000', 'TANAH TANDUS'),
			(149, '1', '01', '09', '01', '001', 'BERBATU-BATU'),
			(150, '1', '01', '09', '01', '002', 'LONGSOR'),
			(151, '1', '01', '09', '01', '003', 'TANAH LAHAR'),
			(152, '1', '01', '09', '01', '004', 'TANAH BERPASIR/PASIR'),
			(153, '1', '01', '09', '01', '005', 'TANAH PENGAMBILAN/KUASI'),
			(154, '1', '01', '09', '01', '999', 'TANAH TANDUS LAINNYA'),
			(155, '1', '01', '09', '02', '000', 'TANAH RUSAK'),
			(156, '1', '01', '09', '02', '001', 'TANAH YANG TEREROSI/LONGSOR'),
			(157, '1', '01', '09', '02', '002', 'BEKAS TAMBANG/GALIAN'),
			(158, '1', '01', '09', '02', '003', 'BEKAS SAWAH/RAWA'),
			(159, '1', '01', '09', '02', '999', 'TANAH RUSAK LAINNYA'),
			(160, '1', '01', '10', '00', '000', 'TANAH ALANG-ALANG DAN PADANG RUMPUT'),
			(161, '1', '01', '10', '01', '000', 'ALANG-ALANG'),
			(162, '1', '01', '10', '01', '001', 'ALANG-ALANG'),
			(163, '1', '01', '10', '01', '999', 'ALANG-ALANG LAINNYA'),
			(164, '1', '01', '10', '02', '000', 'PADANG RUMPUT'),
			(165, '1', '01', '10', '02', '001', 'SEMAK BELUKAR'),
			(166, '1', '01', '10', '02', '002', 'PADANG RUMPUT'),
			(167, '1', '01', '10', '02', '999', 'PADANG RUMPUT LAINNYA'),
			(168, '1', '01', '11', '00', '000', 'TANAH PERTAMBANGAN'),
			(169, '1', '01', '11', '01', '000', 'TANAH PERTAMBANGAN'),
			(170, '1', '01', '11', '01', '001', 'TANAH PERTAMBANGAN INTAN'),
			(171, '1', '01', '11', '01', '002', 'TANAH PERTAMBANGAN EMAS'),
			(172, '1', '01', '11', '01', '003', 'TANAH PERTAMBANGAN PERAK'),
			(173, '1', '01', '11', '01', '004', 'TANAH PERTAMBANGAN NEKEL'),
			(174, '1', '01', '11', '01', '005', 'TANAH PERTAMBANGAN TIMAH'),
			(175, '1', '01', '11', '01', '006', 'TANAH PERTAMBANGAN URANIUM'),
			(176, '1', '01', '11', '01', '007', 'TANAH PERTAMBANGAN TEMBAGA'),
			(177, '1', '01', '11', '01', '008', 'TANAH PERTAMBANGAN MINYAK BUMI'),
			(178, '1', '01', '11', '01', '009', 'TANAH PERTAMBANGAN BATU BARA'),
			(179, '1', '01', '11', '01', '010', 'TANAH PERTAMBANGAN KOSLIN'),
			(180, '1', '01', '11', '01', '011', 'TANAH PERTAMBANGAN BATU BARA BERHARGA'),
			(181, '1', '01', '11', '01', '012', 'TANAH PERTAMBANGAN PASIR BERHARGA'),
			(182, '1', '01', '11', '01', '999', 'TANAH PERTAMBANGAN LAINNYA'),
			(183, '1', '01', '12', '00', '000', 'TANAH UNTUK BANGUNAN GEDUNG'),
			(184, '1', '01', '12', '01', '000', 'TANAH BANGUNAN PERUMAHAN/GDG. TEMPAT TINGGAL'),
			(185, '1', '01', '12', '01', '001', 'TANAH BANGUNAN MESS'),
			(186, '1', '01', '12', '01', '002', 'TANAH BANGUNAN WISMA'),
			(187, '1', '01', '12', '01', '003', 'TANAH BANGUNAN ASRAMA'),
			(188, '1', '01', '12', '01', '004', 'TANAH BANGUNAN PERISTIRAHATAN'),
			(189, '1', '01', '12', '01', '005', 'TANAH BANGUNAN BUNGALAOW'),
			(190, '1', '01', '12', '01', '006', 'TANAH BANGUNAN COTTAGE'),
			(191, '1', '01', '12', '01', '999', 'TANAH BANGUNAN RUMAH TEMPAT TINGGAL LAINNYA'),
			(192, '1', '01', '12', '02', '000', 'TANAH UNTUK BANGUNAN GEDUNG PERDAGANGAN'),
			(193, '1', '01', '12', '02', '001', 'TANAH BANGUNAN PASAR'),
			(194, '1', '01', '12', '02', '002', 'TANAH BANGUNAN PERTOKOAN/RUMAH TOKO'),
			(195, '1', '01', '12', '02', '003', 'TANAH BANGUNAN GUDANG'),
			(196, '1', '01', '12', '02', '004', 'TANAH BANGUNAN BIOSKOP'),
			(197, '1', '01', '12', '02', '005', 'TANAH BANGUNAN HOTEL/PENGINAPAN'),
			(198, '1', '01', '12', '02', '006', 'TANAH BANGUNAN TERMINAL DARAT'),
			(199, '1', '01', '12', '02', '007', 'TANAH BANGUNAN TERMINAL LAUT'),
			(200, '1', '01', '12', '02', '008', 'TANAH BANGUNAN GEDUNG KESENIAN'),
			(201, '1', '01', '12', '02', '009', 'TANAH BANGUNAN GEDUNG PAMERAN'),
			(202, '1', '01', '12', '02', '010', 'TANAH BANGUNAN GEDUNG PUSAT PERBELANJAAN'),
			(203, '1', '01', '12', '02', '011', 'TANAH BANGUNAN APOTIK'),
			(204, '1', '01', '12', '02', '999', 'TANAH BANGUNAN GEDUNG PERDAGANGAN LAINNYA'),
			(205, '1', '01', '12', '03', '000', 'TANAH UNTUK BANGUNAN INDUSTRI'),
			(206, '1', '01', '12', '03', '001', 'TANAH BANGUNAN INDUSTRI MAKANAN'),
			(207, '1', '01', '12', '03', '002', 'TANAH BANGUNAN INDUSTRI MINUMAN'),
			(208, '1', '01', '12', '03', '003', 'TANAH BANGUNAN INDUSTRI/ALAT RT.'),
			(209, '1', '01', '12', '03', '004', 'TANAH BANGUNAN INDUSTRI PAKAIAN/GARMENT'),
			(210, '1', '01', '12', '03', '005', 'TANAH BANGUNAN INDUSTRI BESI/LOGAM'),
			(211, '1', '01', '12', '03', '006', 'TANAH BANGUNAN INDUSTRI BAJA'),
			(212, '1', '01', '12', '03', '007', 'TANAH BANGUNAN INDUSTRI PENGALENGAN'),
			(213, '1', '01', '12', '03', '008', 'TANAH BANGUNAN INDUSTRI BENGKEL'),
			(214, '1', '01', '12', '03', '009', 'TANAH BANGUNAN INDUSTRI PENYULINGAN  MINYAK'),
			(215, '1', '01', '12', '03', '010', 'TANAH BANGUNAN INDUSTRI SEMEN'),
			(216, '1', '01', '12', '03', '011', 'TANAH BANGUNAN INDUSTRI BATU BATA/BATAKO'),
			(217, '1', '01', '12', '03', '012', 'TANAH BANGUNAN INDUSTRI GENTENG'),
			(218, '1', '01', '12', '03', '013', 'TANAH BANGUNAN INDUSTRI PERCETAKAN'),
			(219, '1', '01', '12', '03', '014', 'TANAH BANGUNAN INDUSTRI TESKTIL'),
			(220, '1', '01', '12', '03', '015', 'TANAH BANGUNAN INDUSTRI OBAT-OBATAN'),
			(221, '1', '01', '12', '03', '016', 'TANAH BANGUNAN INDUSTRI ALAT OLAH RAGA'),
			(222, '1', '01', '12', '03', '017', 'TANAH BANGUNAN INDUSTRI KENDARAAN/ OTOMOTIF'),
			(223, '1', '01', '12', '03', '019', 'TANAH BANGUNAN INDUSTRI PERSENJATAAN'),
			(224, '1', '01', '12', '03', '020', 'TANAH BANGUNAN INDUSTRI KAPAL UDARA'),
			(225, '1', '01', '12', '03', '021', 'TANAH BANGUNAN INDUSTRI KAPAL LAUT'),
			(226, '1', '01', '12', '03', '022', 'TANAH BANGUNAN INDUSTRI KAPAL API'),
			(227, '1', '01', '12', '03', '023', 'TANAH BANGUNAN INDUSTRI KERAMIK/MARMER'),
			(228, '1', '01', '12', '03', '999', 'TANAH BANGUNAN INDUSTRI LAINNYA'),
			(229, '1', '01', '12', '04', '000', 'TANAH UNTUK BANGUNAN TEMPAT KERJA/JASA'),
			(230, '1', '01', '12', '04', '001', 'TANAH BANGUNAN KANTOR PEMERINTAH'),
			(231, '1', '01', '12', '04', '002', 'TANAH BANGUNAN SEKOLAH'),
			(232, '1', '01', '12', '04', '003', 'TANAH BANGUNAN RUMAH SAKIT'),
			(233, '1', '01', '12', '04', '004', 'TANAH BANGUNAN APOTIK'),
			(234, '1', '01', '12', '04', '005', 'TANAH BANGUNAN TEMPAT IBADAH'),
			(235, '1', '01', '12', '04', '006', 'TANAH BANGUNAN DERMAGA'),
			(236, '1', '01', '12', '04', '007', 'TANAH BANGUNAN PELABUHAN UDARA'),
			(237, '1', '01', '12', '04', '008', 'TANAH BANGUNAN OLAH RAGA'),
			(238, '1', '01', '12', '04', '009', 'TANAH BANGUNAN TAMAN/WISATA/REKREASI'),
			(239, '1', '01', '12', '04', '010', 'TANAH BANGUNAN BALAI SIDANG/PERTEMUAN'),
			(240, '1', '01', '12', '04', '011', 'TANAH BANGUNAN BALAI NIKAH'),
			(241, '1', '01', '12', '04', '012', 'TANAH BANGUNAN PUSKESMAS/POSYANDU'),
			(242, '1', '01', '12', '04', '013', 'TANAH BANGUNAN POLIKLINIK'),
			(243, '1', '01', '12', '04', '014', 'TANAH BANGUNAN LABORATURIUM'),
			(244, '1', '01', '12', '04', '015', 'TANAH BANGUNAN FUMIGASI/STERLISASI'),
			(245, '1', '01', '12', '04', '016', 'TANAH BANGUNAN KARANTINA'),
			(246, '1', '01', '12', '04', '017', 'TANAH BANGUNAN BANGSAL PENGOLAHAN  PONDON KERJA'),
			(247, '1', '01', '12', '04', '018', 'TANAH BANGUNAN KANDANG HEWAN'),
			(248, '1', '01', '12', '04', '019', 'TANAH BANGUNAN-BANGUNAN PEMBIBITAN'),
			(249, '1', '01', '12', '04', '020', 'TANAH BANGUNAN RUMAH PENDINGIN'),
			(250, '1', '01', '12', '04', '021', 'TANAH BANGUNAN RUMAH PENGERING'),
			(251, '1', '01', '12', '04', '022', 'TANAH BANGUNAN STASIUN PENELITIAN'),
			(252, '1', '01', '12', '04', '023', 'TANAH BANGUNAN GEDUNG PELELANGAN IKAN'),
			(253, '1', '01', '12', '04', '024', 'TANAH BANGUNAN POS JAGA/MENARA JAGA'),
			(254, '1', '01', '12', '04', '999', 'TANAH BANGUNAN TEMPAT KERJA LAINNYA'),
			(255, '1', '01', '12', '05', '000', 'TANAH KOSONG'),
			(256, '1', '01', '12', '05', '001', 'TANAH SAWAH'),
			(257, '1', '01', '12', '05', '002', 'TANAH TEGALAN'),
			(258, '1', '01', '12', '05', '003', 'TANAH KEBUN'),
			(259, '1', '01', '12', '05', '004', 'KEBUN PEMBIBITAN'),
			(260, '1', '01', '12', '05', '999', 'TANAH KOSONG YANG TIDAK DIUSAHAKAN'),
			(261, '1', '01', '12', '06', '000', 'TANAH PETERNAKAN'),
			(262, '1', '01', '12', '06', '001', 'TANAH PETERNAKAN'),
			(263, '1', '01', '12', '06', '999', 'TANAH PETERNAKAN LAINNYA'),
			(264, '1', '01', '12', '07', '000', 'TANAH BANGUNAN PENGAIRAN'),
			(265, '1', '01', '12', '07', '001', 'TANAH WADUK'),
			(266, '1', '01', '12', '07', '002', 'TANAH KOMPLEK BENDUNGAN'),
			(267, '1', '01', '12', '07', '003', 'TANAH JARINGAN/SALURAN'),
			(268, '1', '01', '12', '07', '999', 'TANAH BANGUNAN PENGAIRAN LAINNYA'),
			(269, '1', '01', '12', '08', '000', 'TANAH BANGUNAN JALAN DAN JEMBATAN'),
			(270, '1', '01', '12', '08', '001', 'TANAH JALAN'),
			(271, '1', '01', '12', '08', '002', 'TANAH JEMBATAN'),
			(272, '1', '01', '12', '08', '999', 'TANAH BANGUNAN JALAN DAN JEMBATAN LAINNYA'),
			(273, '1', '01', '12', '09', '000', 'TANAH LEMBIRAN/BANTARAN/LEPE-LEPE/SETREN DST'),
			(274, '1', '01', '12', '09', '001', 'TANAH LEMBIRAN PENGAIRAN'),
			(275, '1', '01', '12', '09', '002', 'TANAH LEMBIRAN JALAN DAN JEMBATAN'),
			(276, '1', '01', '12', '09', '999', 'TANAH LEMBIRAN LAINNYA'),
			(277, '1', '01', '13', '00', '000', 'TANAH UNTUK BANGUNAN BUKAN GEDUNG'),
			(278, '1', '01', '13', '01', '000', 'TANAH LAPANGAN OLAH RAGA'),
			(279, '1', '01', '13', '01', '001', 'TANAH LAPANGAN TENIS'),
			(280, '1', '01', '13', '01', '002', 'TANAH LAPANGAN BASKET'),
			(281, '1', '01', '13', '01', '003', 'TANAH LAPANGAN BADMINTON/BULUTANGKIS'),
			(282, '1', '01', '13', '01', '004', 'TANAH LAPANGAN GOLF'),
			(283, '1', '01', '13', '01', '005', 'TANAH LAPANGAN SEPAK BOLA'),
			(284, '1', '01', '13', '01', '006', 'TANAH LAPANGAN BOLA VOLLY'),
			(285, '1', '01', '13', '01', '007', 'TANAH LAPANGAN SEPAK TAKRAW'),
			(286, '1', '01', '13', '01', '008', 'TAANH LAPANGAN PACUAN KUDA'),
			(287, '1', '01', '13', '01', '009', 'TANAH LAPANGAN BALAP SEPEDA'),
			(288, '1', '01', '13', '01', '010', 'TANAH LAPANGAN ATLETIK'),
			(289, '1', '01', '13', '01', '011', 'TANAH LAPANGAN SOFTBALL'),
			(290, '1', '01', '13', '01', '999', 'TANAH LAPANGAN OLAHRAGA LAINNYA'),
			(291, '1', '01', '13', '02', '000', 'TANAH LAPANGAN PARKIR'),
			(292, '1', '01', '13', '02', '001', 'TANAH LAPANGAN PARKIR KONTRUKSI BETON'),
			(293, '1', '01', '13', '02', '002', 'TANAH LAPANGAN PARKIR KONTRUKSI ASPAL'),
			(294, '1', '01', '13', '02', '003', 'TANAH LAPANGAN PARKIR SIRTU (PASIR BATU)'),
			(295, '1', '01', '13', '02', '004', 'TANAH LAPANGAN PARKIR KONBLOK'),
			(296, '1', '01', '13', '02', '005', 'TANAH LAPANGAN PARKIR TANAH KERAS'),
			(297, '1', '01', '13', '02', '999', 'TANAH LAPANGAN PARKIR LAINNYA'),
			(298, '1', '01', '13', '03', '000', 'TANAH LAPANGAN PENIMBUN BARANG'),
			(299, '1', '01', '13', '03', '001', 'TANAH LAPANGAN PENIMBUN BARANG BELUM DIOLAH'),
			(300, '1', '01', '13', '03', '002', 'TANAH LAPANGAN PENIMBUN BARANG JADI'),
			(301, '1', '01', '13', '03', '003', 'TANAH LAPANGAN PENIMBUN PEMBUANGAN SAMPAH'),
			(302, '1', '01', '13', '03', '004', 'TANAH LAPANGAN PENIMBUN BAHAN BANGUNAN'),
			(303, '1', '01', '13', '03', '005', 'TANAH LAPANGAN PENIMBUN BARANG BUKTI'),
			(304, '1', '01', '13', '03', '999', 'TANAH LAPANGAN PENIMBUN BARANG LAINNYA'),
			(305, '1', '01', '13', '04', '000', 'TANAH LAPANGAN PEMANCAR DAN STUDIO ALAM'),
			(306, '1', '01', '13', '04', '001', 'TANAH LAPANGAN PEMANCAR TV/RADIO/RADAR'),
			(307, '1', '01', '13', '04', '002', 'TANAH LAPANGAN STUDIO ALAM'),
			(308, '1', '01', '13', '04', '003', 'TANAH LAPANGAN PEMANCAR LAINNYA'),
			(309, '1', '01', '13', '04', '999', 'TANAH LAPANGAN PEMANCAR DAN STUDIO ALAM LAINNYA'),
			(310, '1', '01', '13', '05', '000', 'TANAH LAPANGAN PENGUJIAN/PENGOLAHAN'),
			(311, '1', '01', '13', '05', '001', 'TANAH LAPANGAN PENGUJIAN KENDARAAN  BERMOTOR'),
			(312, '1', '01', '13', '05', '002', 'TANAH LAPANGAN PENGELOLAAN BAHAN BANGUNAN'),
			(313, '1', '01', '13', '05', '999', 'TANAH LAPANGAN PENGUJIAN/PENGOLAHAN LAINNYA'),
			(314, '1', '01', '13', '06', '000', 'TANAH LAPANGAN TERBANG'),
			(315, '1', '01', '13', '06', '001', 'TANAH LAPANGAN TERBANG PERINTIS'),
			(316, '1', '01', '13', '06', '002', 'TANAH LAPNGAN KOMERSIAL'),
			(317, '1', '01', '13', '06', '003', 'TANAH LAPANGAN TERBANG KHUSUS/MILITER'),
			(318, '1', '01', '13', '06', '004', 'TANAH LAOPANGAN TERBANG OLAH RAGA'),
			(319, '1', '01', '13', '06', '005', 'TANAH LAPANGAN TERBANG PENDIDIKAN'),
			(320, '1', '01', '13', '06', '999', 'TANAH LAPANGAN TERBANG LAINNYA'),
			(321, '1', '01', '13', '07', '000', 'TANAH UNTUK BANGUNAN JALAN'),
			(322, '1', '01', '13', '07', '001', 'TANAH UNTUK JALAN NASIONAL'),
			(323, '1', '01', '13', '07', '002', 'TANAH UNTUK JALAN PROPINSI'),
			(324, '1', '01', '13', '07', '003', 'TANAH UNTUK JALAN KABUPATEN'),
			(325, '1', '01', '13', '07', '004', 'TANAH UNTUK JALAN KOTAMADYA'),
			(326, '1', '01', '13', '07', '005', 'TANAH UNTUK JALAN DESA'),
			(327, '1', '01', '13', '07', '006', 'TANAH UNTUK JALAN TOL'),
			(328, '1', '01', '13', '07', '007', 'TANAH UNTUK JALAN KERETA API/LORI'),
			(329, '1', '01', '13', '07', '008', 'TANAH UNTUK JALAN LANDASAN PACU PESAWAT TERBANG'),
			(330, '1', '01', '13', '07', '009', 'TANAH UNTUK JALAN KHUSUS/KOMPLEK'),
			(331, '1', '01', '13', '07', '999', 'TANAH UNTUK BANGUNAN JALAN LAINNYA'),
			(332, '1', '01', '13', '08', '000', 'TANAH UNTUK BANGUNAN AIR'),
			(333, '1', '01', '13', '08', '001', 'TANAH UNTUK BANGUNAN AIR IRIGASI'),
			(334, '1', '01', '13', '08', '002', 'TANAH UNTUK BANGUNAN PENGAIRAN PASANG SURUT'),
			(335, '1', '01', '13', '08', '003', 'TANAH UNTUK BANGUNAN PENGEMBANGAN RAWA DAN POLDER'),
			(336, '1', '01', '13', '08', '004', 'TANAH UNTUK BANGUNAN PENGAMAN SUNGAI DAN PENANGGULANGAN BENCANA ALAM'),
			(337, '1', '01', '13', '08', '005', 'TANAH UNTUK BANGUNAN PENGEMBANGAN SUMBER AIR DAN AIR TNH'),
			(338, '1', '01', '13', '08', '006', 'TANAH UNTUK BANGUNAN AIR BERSIH/AIR BAKU'),
			(339, '1', '01', '13', '08', '007', 'TANAH UNTUK BANGUNAN AIR KOTOR'),
			(340, '1', '01', '13', '08', '999', 'TANAH UNTUK BANGUNAN AIR LAINNYA'),
			(341, '1', '01', '13', '09', '000', 'TANAH UNTUK BANGUNAN INSTALASI'),
			(342, '1', '01', '13', '09', '001', 'TANAH UNTUK BANGUNAN INSTALASI AIR BERSIH/AIR BAKU'),
			(343, '1', '01', '13', '09', '002', 'TANAH UNTUK BANGUNAN INSTALASI AIR KOTOR/AIR LIMBAH'),
			(344, '1', '01', '13', '09', '003', 'TANAH UNTUK BANGUNAN INSTALASI PENGELOHAN SAMPAH'),
			(345, '1', '01', '13', '09', '004', 'TANAH UNTUK BANGUNAN INSTALASI PENGOLAHAN BAHAN BANGUNAN'),
			(346, '1', '01', '13', '09', '005', 'TANAH UNTUK BANGUNAN INSTALASI LISTRIK'),
			(347, '1', '01', '13', '09', '006', 'TANAH UNTUK BANGUNAN INSTALASI GARDU LISTRIK'),
			(348, '1', '01', '13', '09', '007', 'TANAH UNTUK BANGUNAN PANGOLAHAN LIMBAH'),
			(349, '1', '01', '13', '09', '999', 'TANAH UNTUK BANGUNAN INSTALASI LAINNYA'),
			(350, '1', '01', '13', '10', '000', 'TANAH UNTUK BANGUNAN JARINGAN'),
			(351, '1', '01', '13', '10', '001', 'TANAH UNTUK BANGUNAN JARINGAN AIR BERSIH/AIR BAKU'),
			(352, '1', '01', '13', '10', '002', 'TANAH UNTUK BANGUNAN JARINGAN KOMUNIKASI'),
			(353, '1', '01', '13', '10', '003', 'TANAH UNTUK BANGUNAN JARINGAN LISTRIK'),
			(354, '1', '01', '13', '10', '004', 'TANAH UNTUK BANGUNAN JARINGAN GAS/BBM'),
			(355, '1', '01', '13', '10', '999', 'TANAH UNTUK BANGUNAN JARINGAN LAINNYA'),
			(356, '1', '01', '13', '11', '000', 'TANAH UNTUK BANGUNAN BERSEJARAH'),
			(357, '1', '01', '13', '11', '001', 'TANAH UNTUK MONUMEN'),
			(358, '1', '01', '13', '11', '002', 'TANAH UNTUK TUGU PERINGATAN'),
			(359, '1', '01', '13', '11', '003', 'TANAH UNTUK TUGU BATAS WILAYAH'),
			(360, '1', '01', '13', '11', '004', 'TANAH UNTUK CANDI'),
			(361, '1', '01', '13', '11', '005', 'TANAH UNTUK BANGUNAN MOSEUM'),
			(362, '1', '01', '13', '11', '006', 'TANAH UNTUK BANGUNAN BERSEJARAH'),
			(363, '1', '01', '13', '11', '999', 'TANAH UNTUK BANGUNAN BERSEJARAH LAINNYA'),
			(364, '1', '01', '13', '12', '000', 'TANAH UNTUK BANGUNAN GEDUNG OLAH RAGA'),
			(365, '1', '01', '13', '12', '001', 'TANAH BANGUNAN SARANA OLAOH RAGA TERBATAS'),
			(366, '1', '01', '13', '12', '002', 'TANAH BANGUNAN SARANA OLAH RAGA TERBUKA'),
			(367, '1', '01', '13', '12', '999', 'TANAH BANGUNAN SARANA OLAH RAGA LAINNYA'),
			(368, '1', '01', '13', '13', '000', 'TANAH UNTUK BANGUNAN TEMPAT IBADAH'),
			(369, '1', '01', '13', '13', '001', 'TANAH UNTUK BANGUNAN MESJID'),
			(370, '1', '01', '13', '13', '002', 'TANAH UNTUK BANGUNAN GEREJA'),
			(371, '1', '01', '13', '13', '003', 'TANAH UNTUK BANGUNAN PURA'),
			(372, '1', '01', '13', '13', '004', 'TANAH UNTUK BANGUNAN VIHARA'),
			(373, '1', '01', '13', '13', '005', 'TANAH UNTUK BANGUNAN KLENTENG/KUIL'),
			(374, '1', '01', '13', '13', '006', 'TANAH UNTUK BANGUNAN KREMATORIUM'),
			(375, '1', '01', '13', '13', '999', 'TANAH UNTUK BANGUNAN TAMPAT IBADAH LAINNYA'),
			(376, '1', '01', '14', '00', '000', 'TANAH PENGGUNAAN LAINNYA'),
			(377, '1', '01', '14', '01', '000', 'PENGGALIAN'),
			(378, '1', '01', '14', '01', '001', 'PENGGALIAN'),
			(379, '1', '01', '14', '01', '002', 'TEMPAT AIR HANGAT'),
			(380, '1', '01', '14', '01', '999', 'TANAH PENGGUNAAN LAINNYA'),
			(381, '2', '00', '00', '00', '000', 'PERALATAN DAN MESIN'),
			(382, '2', '01', '00', '00', '000', 'ALAT BESAR'),
			(383, '2', '01', '01', '00', '000', 'ALAT BESAR DARAT'),
			(384, '2', '01', '01', '01', '000', 'TRACTOR'),
			(385, '2', '01', '01', '01', '001', 'CRAWLER TRACTOR + ATTACHMENT'),
			(386, '2', '01', '01', '01', '002', 'WHEEL TRACTOR + ATTACHMENT'),
			(387, '2', '01', '01', '01', '003', 'SWAMP TRACTOR + ATTACHMENT'),
			(388, '2', '01', '01', '01', '004', 'PRIME MOWER'),
			(389, '2', '01', '01', '01', '005', 'AIRCRAFT TOWING TRACTOR'),
			(390, '2', '01', '01', '01', '006', 'TOWING BAR'),
			(391, '2', '01', '01', '01', '007', 'BULLDOZER'),
			(392, '2', '01', '01', '01', '008', 'WHEEL DOZER'),
			(393, '2', '01', '01', '01', '999', 'TRACTOR LAINNYA'),
			(394, '2', '01', '01', '02', '000', 'GRADER'),
			(395, '2', '01', '01', '02', '001', 'GRADER + ATTACHMENT'),
			(396, '2', '01', '01', '02', '002', 'GRADER TOWED TYPE'),
			(397, '2', '01', '01', '02', '999', 'GRADER LAINNYA'),
			(398, '2', '01', '01', '03', '000', 'EXCAVATOR'),
			(399, '2', '01', '01', '03', '001', 'CRAWLER EXCAVATOR + ATTACHMENT'),
			(400, '2', '01', '01', '03', '002', 'WHEEL EXCAVATOR + ATTACHMENT'),
			(401, '2', '01', '01', '03', '999', 'EXCAVATOR LAINNYA'),
			(402, '2', '01', '01', '04', '000', 'PILE DRIVER'),
			(403, '2', '01', '01', '04', '001', 'DIESEL PILE DRIVER'),
			(404, '2', '01', '01', '04', '002', 'PNEUMATIC PILE DRIVER'),
			(405, '2', '01', '01', '04', '003', 'VIBRATION PILE DRIVER'),
			(406, '2', '01', '01', '04', '999', 'PILE DRIVER LAINNYA'),
			(407, '2', '01', '01', '05', '000', 'HAULER'),
			(408, '2', '01', '01', '05', '001', 'SELF PROPELLED SCRAPER'),
			(409, '2', '01', '01', '05', '002', 'TOWED SCRAPER'),
			(410, '2', '01', '01', '05', '003', 'DUMP TRUCK'),
			(411, '2', '01', '01', '05', '004', 'DUMP WAGON'),
			(412, '2', '01', '01', '05', '005', 'LORI'),
			(413, '2', '01', '01', '05', '999', 'HAULER LAINNYA'),
			(414, '2', '01', '01', '06', '000', 'ASPHALT EQUIPMENT'),
			(415, '2', '01', '01', '06', '001', 'ASPHALT MIXING PLANT'),
			(416, '2', '01', '01', '06', '002', 'ASPHALT FINISHER'),
			(417, '2', '01', '01', '06', '003', 'ASPHALT DISTRIBUTOR'),
			(418, '2', '01', '01', '06', '004', 'ASPHALT HEATER'),
			(419, '2', '01', '01', '06', '005', 'ASPHALT TANKER'),
			(420, '2', '01', '01', '06', '006', 'ASPHALT SPRAYER'),
			(421, '2', '01', '01', '06', '007', 'ASBUTON DRYER'),
			(422, '2', '01', '01', '06', '008', 'ASPHALT RECYCLE'),
			(423, '2', '01', '01', '06', '009', 'COLD MILLING MACHINE'),
			(424, '2', '01', '01', '06', '010', 'ASPHALT MIXER'),
			(425, '2', '01', '01', '06', '011', 'BITUMEN / ASPHALT TEST'),
			(426, '2', '01', '01', '06', '999', 'ASPHALT EQUIPMENT LAINNYA'),
			(427, '2', '01', '01', '07', '000', 'COMPACTING EQUIPMENT'),
			(428, '2', '01', '01', '07', '001', 'MACADAM ROLLER/THREE WHEEL ROLLER'),
			(429, '2', '01', '01', '07', '002', 'TANDEM ROLLER'),
			(430, '2', '01', '01', '07', '003', 'MESH ROLLER'),
			(431, '2', '01', '01', '07', '004', 'VIBRATION ROLLER'),
			(432, '2', '01', '01', '07', '005', 'TYRE ROLLER'),
			(433, '2', '01', '01', '07', '006', 'SOIL STABILIZER'),
			(434, '2', '01', '01', '07', '007', 'SHEEPFOOT/TAMPING ROLLER'),
			(435, '2', '01', '01', '07', '008', 'STAMPER'),
			(436, '2', '01', '01', '07', '009', 'VIBRATION PLATE'),
			(437, '2', '01', '01', '07', '010', 'PEMADAT SAMPAH'),
			(438, '2', '01', '01', '07', '011', 'TRUCK & BUSH TYRE'),
			(439, '2', '01', '01', '07', '999', 'COMPACTING EQUIPMENT LAINNYA'),
			(440, '2', '01', '01', '08', '000', 'AGGREGATE & CONCRETE EQUIPMENT'),
			(441, '2', '01', '01', '08', '001', 'STONE CRUSHING PLANT'),
			(442, '2', '01', '01', '08', '002', 'SCREENING CLASSIFER'),
			(443, '2', '01', '01', '08', '003', 'STONE CHUSER'),
			(444, '2', '01', '01', '08', '004', 'AGGREGATE WASHER'),
			(445, '2', '01', '01', '08', '005', 'BATCHING PLANT'),
			(446, '2', '01', '01', '08', '006', 'CONCRETE FINISHER'),
			(447, '2', '01', '01', '08', '007', 'CONCRETE PUMP'),
			(448, '2', '01', '01', '08', '008', 'CONCRETE LIFT'),
			(449, '2', '01', '01', '08', '009', 'CONCRETE PRESTRES'),
			(450, '2', '01', '01', '08', '010', 'CONCRETE CUTTER'),
			(451, '2', '01', '01', '08', '011', 'CONCRETE MIXER'),
			(452, '2', '01', '01', '08', '012', 'CONCRETE VIBRATOR'),
			(453, '2', '01', '01', '08', '013', 'CONCRETE BREAKER'),
			(454, '2', '01', '01', '08', '014', 'AGGREGATE/CHIP SPREADER'),
			(455, '2', '01', '01', '08', '015', 'GRAUTING MACHINE'),
			(456, '2', '01', '01', '08', '016', 'CONCRETE MOULD'),
			(457, '2', '01', '01', '08', '017', 'PIPE PLANT EQUIPMENT'),
			(458, '2', '01', '01', '08', '018', 'CONCRETE MIXER TANDEM'),
			(459, '2', '01', '01', '08', '019', 'ONION HEAD MACHINE'),
			(460, '2', '01', '01', '08', '020', 'PAN MIXER'),
			(461, '2', '01', '01', '08', '021', 'ASBUTON MIXER'),
			(462, '2', '01', '01', '08', '022', 'PADDLE MIXER'),
			(463, '2', '01', '01', '08', '023', 'ASPHALT BUTON CRUSHER'),
			(464, '2', '01', '01', '08', '024', 'ROCK DRILL'),
			(465, '2', '01', '01', '08', '999', 'AGGREGATE & CONCRETE EQUIPMENT LAINNYA'),
			(466, '2', '01', '01', '09', '000', 'LOADER'),
			(467, '2', '01', '01', '09', '001', 'TRACK LOADER + ATTACHMENT'),
			(468, '2', '01', '01', '09', '002', 'WHEEL LOADER + ATTACHMENT'),
			(469, '2', '01', '01', '09', '003', 'MAIN DECK LOADER'),
			(470, '2', '01', '01', '09', '004', 'CONVEYOR BELT TRUCK'),
			(471, '2', '01', '01', '09', '005', 'HIGH LIFT LOADER'),
			(472, '2', '01', '01', '09', '006', 'BACKHOE LOADER'),
			(473, '2', '01', '01', '09', '999', 'LOADER LAINNYA'),
			(474, '2', '01', '01', '10', '000', 'ALAT PENGANGKAT'),
			(475, '2', '01', '01', '10', '001', 'TOWER CRANE'),
			(476, '2', '01', '01', '10', '002', 'TRUCK MOUNTED CRANE'),
			(477, '2', '01', '01', '10', '003', 'TRUCK CRANE'),
			(478, '2', '01', '01', '10', '004', 'WHEEL CRANE'),
			(479, '2', '01', '01', '10', '005', 'FORKLIFT'),
			(480, '2', '01', '01', '10', '006', 'FORTAL CRANE'),
			(481, '2', '01', '01', '10', '007', 'CRAWLER CRANE'),
			(482, '2', '01', '01', '10', '008', 'CONTAINER CRANE'),
			(483, '2', '01', '01', '10', '009', 'TRANSTAINER'),
			(484, '2', '01', '01', '10', '010', 'TRAVELT CONTAINER STACKER'),
			(485, '2', '01', '01', '10', '011', 'TOP LOADER'),
			(486, '2', '01', '01', '10', '012', 'RAIL LIFTER'),
			(487, '2', '01', '01', '10', '013', 'TRACK MOTOR CAR'),
			(488, '2', '01', '01', '10', '014', 'SALVAGE PESAWAT UDARA'),
			(489, '2', '01', '01', '10', '015', 'HAND PALET TRUCK'),
			(490, '2', '01', '01', '10', '016', 'CRANE SHOVEL 20 T'),
			(491, '2', '01', '01', '10', '017', 'SHOP WOOD WORKING CRANE SHOVEL 20 T'),
			(492, '2', '01', '01', '10', '999', 'ALAT PENGANGKAT LAINNYA'),
			(493, '2', '01', '01', '11', '000', 'MESIN PROSES'),
			(494, '2', '01', '01', '11', '001', 'MESIN PEMBUAT PELLET'),
			(495, '2', '01', '01', '11', '002', 'MESIN PEMBUAT ES'),
			(496, '2', '01', '01', '11', '003', 'MESIN PENGHANCUR ES'),
			(497, '2', '01', '01', '11', '004', 'WATER TREATMENT (MESIN PROSES)'),
			(498, '2', '01', '01', '11', '005', 'SEA WATER TREATMENT'),
			(499, '2', '01', '01', '11', '006', 'MESIN PENGOLAH DODOL'),
			(500, '2', '01', '01', '11', '999', 'MESIN PROSES LAINNYA'),
			(501, '2', '01', '01', '99', '000', 'ALAT BESAR DARAT LAINNYA'),
			(502, '2', '01', '01', '99', '999', 'ALAT BESAR DARAT LAINNYA'),
			(503, '2', '01', '02', '00', '000', 'ALAT BESAR APUNG'),
			(504, '2', '01', '02', '01', '000', 'DREDGER'),
			(505, '2', '01', '02', '01', '001', 'SUCTION DREDGER'),
			(506, '2', '01', '02', '01', '002', 'BUCKET DREDGER'),
			(507, '2', '01', '02', '01', '003', 'CUTTER SUCTION DREDGER'),
			(508, '2', '01', '02', '01', '999', 'DREDGER LAINNYA'),
			(509, '2', '01', '02', '02', '000', 'FLOATING EXCAVATOR'),
			(510, '2', '01', '02', '02', '001', 'FLOATING EXCAVATOR + ATTACHMENT'),
			(511, '2', '01', '02', '02', '002', 'FLOATING CRANE'),
			(512, '2', '01', '02', '02', '003', 'FLOATING PUMP'),
			(513, '2', '01', '02', '02', '999', 'FLOATING EXCAVATOR LAINNYA'),
			(514, '2', '01', '02', '03', '000', 'AMPHIBI DREDGER'),
			(515, '2', '01', '02', '03', '001', 'PLAIN SUCTION'),
			(516, '2', '01', '02', '03', '002', 'CUTTER (AMPHIBI DREDGER)'),
			(517, '2', '01', '02', '03', '003', 'CLAMSHELL / DRAGLINE'),
			(518, '2', '01', '02', '03', '999', 'AMPHIBI DREDGER LAINNYA'),
			(519, '2', '01', '02', '04', '000', 'KAPAL TARIK'),
			(520, '2', '01', '02', '04', '001', 'KAPAL TARIK'),
			(521, '2', '01', '02', '04', '999', 'KAPAL TARIK LAINNYA'),
			(522, '2', '01', '02', '05', '000', 'MESIN PROSES APUNG'),
			(523, '2', '01', '02', '05', '001', 'WATER TREATMENT (MESIN PROSES APUNG)'),
			(524, '2', '01', '02', '05', '999', 'MESIN PROSES APUNG LAINNYA'),
			(525, '2', '01', '02', '99', '000', 'ALAT BESAR APUNG LAINNYA'),
			(526, '2', '01', '02', '99', '999', 'ALAT BESAR APUNG LAINNYA'),
			(527, '2', '01', '03', '00', '000', 'ALAT BANTU'),
			(528, '2', '01', '03', '01', '000', 'ALAT PENARIK'),
			(529, '2', '01', '03', '01', '001', 'ALAT PENARIK KAPAL'),
			(530, '2', '01', '03', '01', '002', 'ALAT PENARIK JARING'),
			(531, '2', '01', '03', '01', '999', 'ALAT PENARIK LAINNYA'),
			(532, '2', '01', '03', '02', '000', 'FEEDER'),
			(533, '2', '01', '03', '02', '001', 'ELEVATOR /LIFT'),
			(534, '2', '01', '03', '02', '002', 'BELT CONVEYOR (FEEDER)'),
			(535, '2', '01', '03', '02', '003', 'SCREW CONVEYOR (FEEDER)'),
			(536, '2', '01', '03', '02', '004', 'ESCALATOR'),
			(537, '2', '01', '03', '02', '005', 'GANDOLA'),
			(538, '2', '01', '03', '02', '006', 'ELEVATOR (FEEDER)'),
			(539, '2', '01', '03', '02', '007', 'GANGWAY'),
			(540, '2', '01', '03', '02', '999', 'FEEDER LAINNYA (ALAT BESAR)'),
			(541, '2', '01', '03', '03', '000', 'COMPRESSOR'),
			(542, '2', '01', '03', '03', '001', 'TRANSPORTABLE COMPRESSOR'),
			(543, '2', '01', '03', '03', '002', 'PORTABLE COMPRESSOR'),
			(544, '2', '01', '03', '03', '003', 'STATIONARY COMPRESSOR'),
			(545, '2', '01', '03', '03', '004', 'AIR COMPRESOR'),
			(546, '2', '01', '03', '03', '005', 'COMPRESSOR PNEUMATIC TOOL 25 GMP'),
			(547, '2', '01', '03', '03', '999', 'COMPRESSOR LAINNYA'),
			(548, '2', '01', '03', '04', '000', 'ELECTRIC GENERATING SET'),
			(549, '2', '01', '03', '04', '001', 'TRANSPORTABLE GENERATING SET'),
			(550, '2', '01', '03', '04', '002', 'PORTABLE GENERATING SET'),
			(551, '2', '01', '03', '04', '003', 'STATIONARY GENERATING SET'),
			(552, '2', '01', '03', '04', '004', 'DYNAMO ELECTRIC'),
			(553, '2', '01', '03', '04', '999', 'ELECTRIC GENERATING SET LAINNYA'),
			(554, '2', '01', '03', '05', '000', 'POMPA'),
			(555, '2', '01', '03', '05', '001', 'TRANSPORTABLE WATER PUMP'),
			(556, '2', '01', '03', '05', '002', 'PORTABLE WATER PUMP'),
			(557, '2', '01', '03', '05', '003', 'STATIONARY WATER PUMP'),
			(558, '2', '01', '03', '05', '004', 'POMPA LUMPUR'),
			(559, '2', '01', '03', '05', '005', 'SUMERSIBLE PUMP'),
			(560, '2', '01', '03', '05', '006', 'POMPA TANGAN'),
			(561, '2', '01', '03', '05', '007', 'POMPA ANGIN'),
			(562, '2', '01', '03', '05', '008', 'POMPA BENSIN/MINYAK STATIONERY'),
			(563, '2', '01', '03', '05', '009', 'POMPA BENSIN/MINYAK TRANSPORTABLE'),
			(564, '2', '01', '03', '05', '010', 'POMPA AIR'),
			(565, '2', '01', '03', '05', '011', 'WATER DISTRIBUTOR'),
			(566, '2', '01', '03', '05', '012', 'WATER PURIFICATION'),
			(567, '2', '01', '03', '05', '999', 'POMPA LAINNYA'),
			(568, '2', '01', '03', '06', '000', 'MESIN BOR'),
			(569, '2', '01', '03', '06', '001', 'MESIN BOR BATU'),
			(570, '2', '01', '03', '06', '002', 'MESIN BOR TANAH'),
			(571, '2', '01', '03', '06', '003', 'MESIN BOR BETON'),
			(572, '2', '01', '03', '06', '999', 'MESIN BOR LAINNYA'),
			(573, '2', '01', '03', '07', '000', 'UNIT PEMELIHARAAN LAPANGAN'),
			(574, '2', '01', '03', '07', '001', 'MOBIL WORKSHOP'),
			(575, '2', '01', '03', '07', '002', 'SERVICE CAR'),
			(576, '2', '01', '03', '07', '003', 'FLOATING WORKSHOP'),
			(577, '2', '01', '03', '07', '004', 'ROAD MAINTENANCE TRUCK'),
			(578, '2', '01', '03', '07', '005', 'SWEEPER TRUCK'),
			(579, '2', '01', '03', '07', '006', 'WRECK CAR'),
			(580, '2', '01', '03', '07', '007', 'LEAK DETECTOR (UNIT PEMELIHARAAN LAPANGAN)'),
			(581, '2', '01', '03', '07', '008', 'PIPE LOCATOR'),
			(582, '2', '01', '03', '07', '009', 'METAL LOCATOR'),
			(583, '2', '01', '03', '07', '010', 'MESIN DIESEL'),
			(584, '2', '01', '03', '07', '011', 'KETLE HEATING'),
			(585, '2', '01', '03', '07', '012', 'SWEEPER PENGHISAP OLI'),
			(586, '2', '01', '03', '07', '013', 'FUEL TANK'),
			(587, '2', '01', '03', '07', '014', 'GRASS COLECTOR'),
			(588, '2', '01', '03', '07', '015', 'MESIN PEMOTONG ASPAL (DRAGING)'),
			(589, '2', '01', '03', '07', '016', 'SWEEPER ROTARY'),
			(590, '2', '01', '03', '07', '017', 'EARTH VAGER TRUCK'),
			(591, '2', '01', '03', '07', '018', 'SCRAPPER'),
			(592, '2', '01', '03', '07', '019', 'ROSTER'),
			(593, '2', '01', '03', '07', '020', 'SHOP TRUCK EQUIPMENT'),
			(594, '2', '01', '03', '07', '999', 'UNIT PEMELIHARAAN LAPANGAN LAINNYA'),
			(595, '2', '01', '03', '08', '000', 'ALAT PENGOLAHAN AIR KOTOR'),
			(596, '2', '01', '03', '08', '001', 'UNIT PENGOLAHAN AIR KOTOR'),
			(597, '2', '01', '03', '08', '999', 'ALAT PENGOLAHAN AIR KOTOR LAINNYA'),
			(598, '2', '01', '03', '09', '000', 'PEMBANGKIT UAP AIR PANAS/STEAM GENERATOR'),
			(599, '2', '01', '03', '09', '001', 'UNIT PEMBANGKIT UAP AIR PANAS'),
			(600, '2', '01', '03', '09', '999', 'PEMBANGKIT UAP AIR PANAS/STEAM GENERATOR LAINNYA'),
			(601, '2', '01', '03', '12', '000', 'PERALATAN KEBAKARAN HUTAN'),
			(602, '2', '01', '03', '12', '001', 'BACKPACK PUMP (POMPA PUNGGUNG BESAR)'),
			(603, '2', '01', '03', '12', '002', 'FLOATING FIRE PUMP (POMPA PUNGGUNG KECIL)'),
			(604, '2', '01', '03', '12', '003', 'POMPA PORTABLE'),
			(605, '2', '01', '03', '12', '004', 'JET SHOOTER'),
			(606, '2', '01', '03', '12', '005', 'GOLOK PEMADAM'),
			(607, '2', '01', '03', '12', '006', 'BLADE SHOVEL (SEKOP PEMADAM)'),
			(608, '2', '01', '03', '12', '007', 'SUMBUT'),
			(609, '2', '01', '03', '12', '008', 'VELD BED'),
			(610, '2', '01', '03', '12', '009', 'RANSEL PEMADAM'),
			(611, '2', '01', '03', '12', '010', 'FULL BODY HARNESS'),
			(612, '2', '01', '03', '12', '011', 'SIT HARNESS'),
			(613, '2', '01', '03', '12', '012', 'FIGURE'),
			(614, '2', '01', '03', '12', '013', 'ASCENDER'),
			(615, '2', '01', '03', '12', '014', 'SCROLL LOCK'),
			(616, '2', '01', '03', '12', '015', 'PERLENGKAPAN RESCUE'),
			(617, '2', '01', '03', '12', '016', 'AUTOMATIC SNAP HOOK'),
			(618, '2', '01', '03', '12', '017', 'TANGGA TALI'),
			(619, '2', '01', '03', '12', '018', 'NOZEL TABIR ALUMUNIUM'),
			(620, '2', '01', '03', '12', '019', 'NOZEL KUNINGAN PERNEKEL'),
			(621, '2', '01', '03', '12', '020', 'SELANG AIR'),
			(622, '2', '01', '03', '12', '021', 'BREATHING APARATUS (TABUNG 10 KG)'),
			(623, '2', '01', '03', '12', '022', 'GEPYOK PEMADAM'),
			(624, '2', '01', '03', '12', '023', 'FIRE RAKE (GARU TAJAM)'),
			(625, '2', '01', '03', '12', '024', 'PULASKI AXE (KAPAK DUA FUNGSI)'),
			(626, '2', '01', '03', '12', '025', 'FIRE TOOL (GARU PACUL/ CANGKUL)'),
			(627, '2', '01', '03', '12', '026', 'SABIT SEMAK'),
			(628, '2', '01', '03', '12', '027', 'FLAPPER (PEMUKUL API)'),
			(629, '2', '01', '03', '12', '028', 'DRIP TORCH (OBOR SULUT TETES)'),
			(630, '2', '01', '03', '12', '029', 'FILES (KIKIR BAJA)'),
			(631, '2', '01', '03', '12', '030', 'KACA MATA (LENSA TAHAN PANAS)'),
			(632, '2', '01', '03', '12', '031', 'KOPEL REM'),
			(633, '2', '01', '03', '12', '032', 'FELPES'),
			(634, '2', '01', '03', '12', '033', 'KANTONG AIR'),
			(635, '2', '01', '03', '12', '034', 'BATANG POMPA'),
			(636, '2', '01', '03', '12', '999', 'PERALATAN KEBAKARAN HUTAN LAINNYA'),
			(637, '2', '01', '03', '13', '000', 'PERALATAN SELAM'),
			(638, '2', '01', '03', '13', '001', 'TANKS (TABUNG SELAM)'),
			(639, '2', '01', '03', '13', '002', 'SEPATU KARANG'),
			(640, '2', '01', '03', '13', '003', 'KNIVES (PISAU SELAM)'),
			(641, '2', '01', '03', '13', '004', 'DIVE LIGHTS (SENTER SELAM)'),
			(642, '2', '01', '03', '13', '005', 'REGULATOR INSTRUMENTS'),
			(643, '2', '01', '03', '13', '006', 'BOUYANCY COMPENSATOR DEVICE (BCD)'),
			(644, '2', '01', '03', '13', '007', 'BELT (SABUK PEMBERAT)'),
			(645, '2', '01', '03', '13', '008', 'WEIGHT (PEMBERAT)'),
			(646, '2', '01', '03', '13', '009', 'DIVING GLOVES (SARUNG TANGAN SELAM)'),
			(647, '2', '01', '03', '13', '010', 'KOMPRESOR SELAM'),
			(648, '2', '01', '03', '13', '011', 'PELAMPUNG LIFE JACKET'),
			(649, '2', '01', '03', '13', '999', 'PERALATAN SELAM LAINNYA'),
			(650, '2', '01', '03', '14', '000', 'PERALATAN SAR MOUNTENERING'),
			(651, '2', '01', '03', '14', '001', 'TALI KAMANTEL STATIC'),
			(652, '2', '01', '03', '14', '002', 'TALI KAMANTEL DINAMIC'),
			(653, '2', '01', '03', '14', '003', 'RAINCOAT (PONCO)'),
			(654, '2', '01', '03', '14', '004', 'SEAT HARNESS'),
			(655, '2', '01', '03', '14', '005', 'PRUSIK'),
			(656, '2', '01', '03', '14', '006', 'JUMMAR'),
			(657, '2', '01', '03', '14', '007', 'PULLEY'),
			(658, '2', '01', '03', '14', '008', 'DESCENDER FIGURE OG EIGHT'),
			(659, '2', '01', '03', '14', '009', 'CARABINER NON SCREW'),
			(660, '2', '01', '03', '14', '010', 'WEBBING'),
			(661, '2', '01', '03', '14', '011', 'TANDU LIPAT'),
			(662, '2', '01', '03', '14', '999', 'PERALATAN SAR MOUNTENERING LAINNYA'),
			(663, '2', '01', '03', '99', '000', 'ALAT BANTU LAINNYA'),
			(664, '2', '01', '03', '99', '999', 'ALAT BANTU LAINNYA'),
			(665, '2', '02', '00', '00', '000', 'ALAT ANGKUTAN'),
			(666, '2', '02', '01', '00', '000', 'ALAT ANGKUTAN DARAT BERMOTOR'),
			(667, '2', '02', '01', '01', '000', 'KENDARAAN DINAS BERMOTOR PERORANGAN'),
			(668, '2', '02', '01', '01', '001', 'SEDAN'),
			(669, '2', '02', '01', '01', '002', 'JEEP'),
			(670, '2', '02', '01', '01', '003', 'STATION WAGON'),
			(671, '2', '02', '01', '01', '999', 'KENDARAAN DINAS BERMOTOR PERORANGAN LAINNYA'),
			(672, '2', '02', '01', '02', '000', 'KENDARAAN BERMOTOR PENUMPANG'),
			(673, '2', '02', '01', '02', '001', 'BUS ( PENUMPANG 30 ORANG KEATAS )'),
			(674, '2', '02', '01', '02', '002', 'MICRO BUS ( PENUMPANG 15 S/D 29 ORANG )'),
			(675, '2', '02', '01', '02', '003', 'MINI BUS ( PENUMPANG 14 ORANG KEBAWAH )'),
			(676, '2', '02', '01', '02', '004', 'KENDARAAN LAPIS BAJA'),
			(677, '2', '02', '01', '02', '999', 'KENDARAAN BERMOTOR PENUMPANG LAINNYA'),
			(678, '2', '02', '01', '03', '000', 'KENDARAAN BERMOTOR ANGKUTAN BARANG'),
			(679, '2', '02', '01', '03', '001', 'TRUCK + ATTACHMENT'),
			(680, '2', '02', '01', '03', '002', 'PICK UP'),
			(681, '2', '02', '01', '03', '003', 'YEENGLER/TRAILER'),
			(682, '2', '02', '01', '03', '004', 'SEMI TRAILER'),
			(683, '2', '02', '01', '03', '005', 'TRUCK PONTON DENGAN TRAILLER'),
			(684, '2', '02', '01', '03', '006', 'DALHURA'),
			(685, '2', '02', '01', '03', '999', 'KENDARAAN BERMOTOR ANGKUTAN BARANG LAINNYA'),
			(686, '2', '02', '01', '04', '000', 'KENDARAAN BERMOTOR BERODA DUA'),
			(687, '2', '02', '01', '04', '001', 'SEPEDA MOTOR'),
			(688, '2', '02', '01', '04', '002', 'SCOOTER'),
			(689, '2', '02', '01', '04', '003', 'SEPEDA MOTOR PERPUSTAKAAN KELILING'),
			(690, '2', '02', '01', '04', '004', 'SEPEDA MOTOR PATROLI'),
			(691, '2', '02', '01', '04', '005', 'SEPEDA MOTOR PENGAWALAN'),
			(692, '2', '02', '01', '04', '999', 'KENDARAAN BERMOTOR BERODA DUA LAINNYA'),
			(693, '2', '02', '01', '05', '000', 'KENDARAAN BERMOTOR KHUSUS'),
			(694, '2', '02', '01', '05', '001', 'MOBIL AMBULANCE'),
			(695, '2', '02', '01', '05', '002', 'MOBIL JENAZAH'),
			(696, '2', '02', '01', '05', '003', 'MOBIL UNIT PENERANGAN DARAT'),
			(697, '2', '02', '01', '05', '004', 'MOBIL PEMADAM KEBAKARAN'),
			(698, '2', '02', '01', '05', '005', 'MOBIL TINJA'),
			(699, '2', '02', '01', '05', '006', 'MOBIL TANGKI AIR'),
			(700, '2', '02', '01', '05', '007', 'MOBIL UNIT MONITORING FREKWENSI'),
			(701, '2', '02', '01', '05', '008', 'MOBIL UNIT PERPUSTAKAAN KELILING'),
			(702, '2', '02', '01', '05', '009', 'MOBIL UNIT VISUAL MINI (MUVIANI)'),
			(703, '2', '02', '01', '05', '010', 'MOBIL UNIT SATELITE LINK VAN'),
			(704, '2', '02', '01', '05', '011', 'MOBIL UNIT PANGGUNG'),
			(705, '2', '02', '01', '05', '012', 'MOBIL UNIT PAMERAN'),
			(706, '2', '02', '01', '05', '013', 'OUT SIDE BROAD CAST VAN RADIO'),
			(707, '2', '02', '01', '05', '014', 'OUT SIDE BROAD CAST VAN TELEVISI'),
			(708, '2', '02', '01', '05', '015', 'MOBIL UNIT PRODUKSI FILM'),
			(709, '2', '02', '01', '05', '016', 'MOBIL UNIT PRODUKSI TELEVISI'),
			(710, '2', '02', '01', '05', '017', 'MOBIL UNIT PRODUKSI CINERAMA'),
			(711, '2', '02', '01', '05', '018', 'MOBIL UNIT KESEHATAN MASYARAKAT'),
			(712, '2', '02', '01', '05', '019', 'MOBIL UNIT KESEHATAN HEWAN'),
			(713, '2', '02', '01', '05', '020', 'MOBIL UNIT TAHANAN'),
			(714, '2', '02', '01', '05', '021', 'MOBIL UNIT PENGANGKUT UANG'),
			(715, '2', '02', '01', '05', '022', 'TRUCK SAMPAH'),
			(716, '2', '02', '01', '05', '023', 'MOBIL TANGKI BAHAN BAKAR'),
			(717, '2', '02', '01', '05', '024', 'MOBIL UNIT RONTGEN'),
			(718, '2', '02', '01', '05', '025', 'MOBIL UNIT REHABILITASI SOSIAL KELILING'),
			(719, '2', '02', '01', '05', '026', 'BOMP TRAILER'),
			(720, '2', '02', '01', '05', '027', 'KENDARAAN KLINIK'),
			(721, '2', '02', '01', '05', '028', 'MOBIL UNIT PENGANGKUT LIMBAH RADIO AKTIF'),
			(722, '2', '02', '01', '05', '029', 'MOBIL TRANFUSI DARAH'),
			(723, '2', '02', '01', '05', '030', 'KENDARAAN TIM PEMELIHARAAN'),
			(724, '2', '02', '01', '05', '031', 'MOBIL PENARIK (UNIMOG)'),
			(725, '2', '02', '01', '05', '032', 'KENDARAAN SATMOBEK/SATMOBENG/SATMOMAS'),
			(726, '2', '02', '01', '05', '033', 'MOBIL WORK SHOP/SERVICES'),
			(727, '2', '02', '01', '05', '034', 'KENDARAAN DEREK'),
			(728, '2', '02', '01', '05', '035', 'MOBIL UNIT KHUSUS ALJIHANDAK'),
			(729, '2', '02', '01', '05', '036', 'AIRCRAFT AIR CONDITIONING'),
			(730, '2', '02', '01', '05', '037', 'KENDARAAN GIRAFLE RADAR'),
			(731, '2', '02', '01', '05', '038', 'MOBIL PERS VAN'),
			(732, '2', '02', '01', '05', '039', 'KENDARAAN UNIT BEDAH'),
			(733, '2', '02', '01', '05', '040', 'MOBILE FLOODLIGHT'),
			(734, '2', '02', '01', '05', '041', 'KENDARAAN PENGANGKUT TANK'),
			(735, '2', '02', '01', '05', '042', 'CRASH CAR'),
			(736, '2', '02', '01', '05', '043', 'KENDARAAN WATER CANON'),
			(737, '2', '02', '01', '05', '044', 'FOAM VEHICLE'),
			(738, '2', '02', '01', '05', '045', 'KENDARAAN TOILET'),
			(739, '2', '02', '01', '05', '046', 'RAPID INVENTION VEHICLE'),
			(740, '2', '02', '01', '05', '047', 'KENDARAAN GAS AIRMATA'),
			(741, '2', '02', '01', '05', '048', 'KENDARAAN TAKTIS'),
			(742, '2', '02', '01', '05', '049', 'KENDARAAN VIP (ANTI PELURU)'),
			(743, '2', '02', '01', '05', '050', 'KENDARAAN TANGGA PESAWAT'),
			(744, '2', '02', '01', '05', '051', 'KENDARAAN METEO'),
			(745, '2', '02', '01', '05', '052', 'KENDARAAN SWEEPER'),
			(746, '2', '02', '01', '05', '053', 'KENDARAAN KAMAR SANDI'),
			(747, '2', '02', '01', '05', '054', 'KENDARAAN JAMMING FREKUENSI'),
			(748, '2', '02', '01', '05', '055', 'KENDARAAN MONITORING SINYAL'),
			(749, '2', '02', '01', '05', '056', 'MOBIL DAPUR LAPANGAN'),
			(750, '2', '02', '01', '05', '057', 'MOBIL PENARIK BARRIER'),
			(751, '2', '02', '01', '05', '058', 'MOBIL OPERASIONAL PJR'),
			(752, '2', '02', '01', '05', '059', 'AUTOMATIC UNGUIDED VEHICLE (AUGV)'),
			(753, '2', '02', '01', '05', '060', 'RESCUE CAR'),
			(754, '2', '02', '01', '05', '061', 'RAPID DEPLOYMENT LAND SAR'),
			(755, '2', '02', '01', '05', '062', 'RESCUE TRUCK'),
			(756, '2', '02', '01', '05', '063', 'MONILOG (MOBIL LOGISTIK/ PERSONIL)'),
			(757, '2', '02', '01', '05', '064', 'MOBIL LATIH'),
			(758, '2', '02', '01', '05', '065', 'RAN SWITCH WAGON'),
			(759, '2', '02', '01', '05', '066', 'RAN CACDRI WAGON'),
			(760, '2', '02', '01', '05', '067', 'RAN TRAKTOR'),
			(761, '2', '02', '01', '05', '068', 'RAN TANGKI'),
			(762, '2', '02', '01', '05', '069', 'RAN ZAT ASAM'),
			(763, '2', '02', '01', '05', '070', 'RAN PENYAPU LANDASAN'),
			(764, '2', '02', '01', '05', '071', 'RAN PANDU PESAWAT'),
			(765, '2', '02', '01', '05', '072', 'RAN PENARIK PESAWAT'),
			(766, '2', '02', '01', '05', '073', 'RAN PENYAPU HANGGAR'),
			(767, '2', '02', '01', '05', '074', 'RAN DRUG CHUTE'),
			(768, '2', '02', '01', '05', '075', 'RAN PEMBANGKIT TENAGA'),
			(769, '2', '02', '01', '05', '076', 'RAN CRIME SQUID'),
			(770, '2', '02', '01', '05', '077', 'RAN WEAPON CARRIER'),
			(771, '2', '02', '01', '05', '078', 'RAN LABORATORIUM / UJI COBA'),
			(772, '2', '02', '01', '05', '079', 'RAN KANTIN'),
			(773, '2', '02', '01', '05', '080', 'RAN PATROLI'),
			(774, '2', '02', '01', '05', '081', 'RAN JEEP KOMMAB'),
			(775, '2', '02', '01', '05', '082', 'RAN RECOVERY'),
			(776, '2', '02', '01', '05', '083', 'RAN PENGISI BB PESAWAT'),
			(777, '2', '02', '01', '05', '084', 'RAN WRECKER'),
			(778, '2', '02', '01', '05', '085', 'RAN FORKLIP'),
			(779, '2', '02', '01', '05', '086', 'MOBIL PATROLI'),
			(780, '2', '02', '01', '05', '087', 'KENDARAAN APC'),
			(781, '2', '02', '01', '05', '088', 'KENDARAAN DARE V'),
			(782, '2', '02', '01', '05', '089', 'KENDARAAN/MOBIL PENGAWALAN'),
			(783, '2', '02', '01', '05', '090', 'MOBIL IRUP'),
			(784, '2', '02', '01', '05', '091', 'MOBIL KOMLEK POLRI'),
			(785, '2', '02', '01', '05', '092', 'MOBIL UNIT TKP'),
			(786, '2', '02', '01', '05', '093', 'MOBIL UNIT LAKA LANTAS'),
			(787, '2', '02', '01', '05', '094', 'MOBIL UNIT IDENTIFIKASI'),
			(788, '2', '02', '01', '05', '095', 'MOBIL UNIT LABFOR'),
			(789, '2', '02', '01', '05', '096', 'MOBIL UNIT PENERANGAN POLRI'),
			(790, '2', '02', '01', '05', '097', 'MOBIL UNIT DEREK'),
			(791, '2', '02', '01', '05', '098', 'MOBIL UNIT SATWA'),
			(792, '2', '02', '01', '05', '099', 'RANTIS PHH'),
			(793, '2', '02', '01', '05', '100', 'KENDARAAN POS POLISI MOBILE'),
			(794, '2', '02', '01', '05', '101', 'MOBIL UNIT ALSUS JIHANDAK'),
			(795, '2', '02', '01', '05', '102', 'MOBIL GOLFCAR'),
			(796, '2', '02', '01', '05', '103', 'RANTIS RESCUE SAMAPTA'),
			(797, '2', '02', '01', '05', '104', 'RANSUS SATWA ANJING TYPE KECIL'),
			(798, '2', '02', '01', '05', '105', 'RANSUS SATWA ANJING TYPE SEDANG'),
			(799, '2', '02', '01', '05', '106', 'RANSUS SATWA ANJING TYPE BESAR'),
			(800, '2', '02', '01', '05', '107', 'RANSUS SATWA KUDA TYPE SEDANG'),
			(801, '2', '02', '01', '05', '108', 'RANSUS SATWA KUDA TYPE BESAR'),
			(802, '2', '02', '01', '05', '109', 'TRAILER KUDA'),
			(803, '2', '02', '01', '05', '999', 'KENDARAAN BERMOTOR KHUSUS LAINNYA'),
			(804, '2', '02', '01', '99', '000', 'ALAT ANGKUTAN DARAT BERMOTOR LAINNYA'),
			(805, '2', '02', '01', '99', '999', 'ALAT ANGKUTAN DARAT BERMOTOR LAINNYA'),
			(806, '2', '02', '02', '00', '000', 'ALAT ANGKUTAN DARAT TAK BERMOTOR'),
			(807, '2', '02', '02', '01', '000', 'KENDARAAN TAK BERMOTOR ANGKUTAN BARANG'),
			(808, '2', '02', '02', '01', '001', 'GEROBAK TARIK'),
			(809, '2', '02', '02', '01', '002', 'GEROBAK DORONG'),
			(810, '2', '02', '02', '01', '003', 'CARAVAN'),
			(811, '2', '02', '02', '01', '004', 'LORI DORONG'),
			(812, '2', '02', '02', '01', '005', 'TRAILER'),
			(813, '2', '02', '02', '01', '006', 'CONTAINER DOLLY'),
			(814, '2', '02', '02', '01', '007', 'PALLET DOLLY'),
			(815, '2', '02', '02', '01', '008', 'BAGGAGE AND MAIL CART'),
			(816, '2', '02', '02', '01', '009', 'BAGGAGE TROLLY'),
			(817, '2', '02', '02', '01', '010', 'MEJA DORONG SAJI/TROLLEY SAJI'),
			(818, '2', '02', '02', '01', '011', 'RODA DUA BERINSULASI'),
			(819, '2', '02', '02', '01', '012', 'RODA TIGA/ GEROBAK KAYUH BERINSULASI'),
			(820, '2', '02', '02', '01', '999', 'KENDARAAN TAK BERMOTOR ANGKUTAN BARANG LAINNYA'),
			(821, '2', '02', '02', '02', '000', 'KENDARAAN TAK BERMOTOR PENUMPANG'),
			(822, '2', '02', '02', '02', '001', 'SEPEDA'),
			(823, '2', '02', '02', '02', '002', 'KUDA (KENDARAAN TAK BERMOTOR PENUMPANG)'),
			(824, '2', '02', '02', '02', '999', 'KENDARAAN TAK BERMOTOR PENUMPANG LAINNYA'),
			(825, '2', '02', '02', '03', '000', 'ALAT ANGKUTAN KERETA REL TAK BERMOTOR'),
			(826, '2', '02', '02', '03', '001', 'KERETA PENUMPANG'),
			(827, '2', '02', '02', '03', '002', 'KERETA MAKAN'),
			(828, '2', '02', '02', '03', '003', 'POWER CAR'),
			(829, '2', '02', '02', '03', '004', 'GERBONG BARANG TERTUTUP'),
			(830, '2', '02', '02', '03', '005', 'GERBONG BARANG TERBUKA'),
			(831, '2', '02', '02', '03', '999', 'ALAT ANGKUTAN KERETA REL TAK BERMOTOR LAINNYA'),
			(832, '2', '02', '02', '99', '000', 'ALAT ANGKUTAN DARAT TAK BERMOTOR LAINNYA'),
			(833, '2', '02', '02', '99', '999', 'ALAT ANGKUTAN DARAT TAK BERMOTOR LAINNYA'),
			(834, '2', '02', '03', '00', '000', 'ALAT ANGKUTAN APUNG BERMOTOR'),
			(835, '2', '02', '03', '01', '000', 'ALAT ANGKUTAN APUNG BERMOTOR UNTUK BARANG'),
			(836, '2', '02', '03', '01', '001', 'KAPAL MINYAK (TANKER)'),
			(837, '2', '02', '03', '01', '002', 'TONGKANG BERMOTOR'),
			(838, '2', '02', '03', '01', '003', 'TUG BOAT + ATTACHMENT'),
			(839, '2', '02', '03', '01', '004', 'LANDING SHIP TRANSPORTATION( L.S.T )'),
			(840, '2', '02', '03', '01', '005', 'KAPAL CARGO (KAPAL BARANG)'),
			(841, '2', '02', '03', '01', '006', 'TRUCK AIR'),
			(842, '2', '02', '03', '01', '999', 'ALAT ANGKUTAN APUNG BERMOTOR UNTUK BARANG LAINNYA'),
			(843, '2', '02', '03', '02', '000', 'ALAT ANGKUTAN APUNG BERMOTOR UNTUK PENUMPANG'),
			(844, '2', '02', '03', '02', '001', 'SPEED BOAT / MOTOR TEMPEL'),
			(845, '2', '02', '03', '02', '002', 'MOTOR BOAT'),
			(846, '2', '02', '03', '02', '003', 'KLOTOK'),
			(847, '2', '02', '03', '02', '004', 'FERRY'),
			(848, '2', '02', '03', '02', '005', 'HIDROFOIL'),
			(849, '2', '02', '03', '02', '006', 'JETFOIL'),
			(850, '2', '02', '03', '02', '007', 'LONG BOAT'),
			(851, '2', '02', '03', '02', '008', 'KAPAL PASSANGER (KAPAL PENUMPANG)'),
			(852, '2', '02', '03', '02', '009', 'PERAHU KAYU'),
			(853, '2', '02', '03', '02', '999', 'ALAT ANGKUTAN APUNG BERMOTOR UNTUK PENUMPANG LAINNYA'),
			(854, '2', '02', '03', '03', '000', 'ALAT ANGKUTAN APUNG BERMOTOR KHUSUS'),
			(855, '2', '02', '03', '03', '001', 'SURVEY BOAT'),
			(856, '2', '02', '03', '03', '002', 'KAPAL ANTI POLUSI'),
			(857, '2', '02', '03', '03', '003', 'KAPAL PERAMBUAN'),
			(858, '2', '02', '03', '03', '004', 'OUT BOAT MOTOR'),
			(859, '2', '02', '03', '03', '005', 'KAPAL HYDROGRAFI'),
			(860, '2', '02', '03', '03', '006', 'KAPAL UNIT PENERANGAN AIR'),
			(861, '2', '02', '03', '03', '007', 'KAPAL VISUAL MINI'),
			(862, '2', '02', '03', '03', '008', 'KAPAL PENANGKAP IKAN'),
			(863, '2', '02', '03', '03', '009', 'KAPAL PENGANGKUT HEWAN'),
			(864, '2', '02', '03', '03', '010', 'KAPAL PATROLI PANTAI'),
			(865, '2', '02', '03', '03', '011', 'KAPAL MOTOR PERPUSTAKAAN KELILING'),
			(866, '2', '02', '03', '03', '012', 'FLOATING WORK SHOP/DOCK'),
			(867, '2', '02', '03', '03', '013', 'MORING BOAT/KEPIL'),
			(868, '2', '02', '03', '03', '014', 'SUCTION DREDGER/KERUK HISAP'),
			(869, '2', '02', '03', '03', '015', 'QUTTER DREDGER/KERUK BOR'),
			(870, '2', '02', '03', '03', '016', 'BUCKET DREDGER/KERUK TIMBA'),
			(871, '2', '02', '03', '03', '017', 'CLAMPSHEL DREDGER/KERUK CAKRAM'),
			(872, '2', '02', '03', '03', '018', 'ALAT ANGKUTAN APUNG UNTUK MANCING'),
			(873, '2', '02', '03', '03', '019', 'FLOATING PILE + ATTACHMENT (ALAT ANGKUTAN APUNG BERMOTOR KHUSUS)'),
			(874, '2', '02', '03', '03', '020', 'SEKOCI MOTOR TEMPEL'),
			(875, '2', '02', '03', '03', '021', 'PERAHU MOTOR TEMPEL'),
			(876, '2', '02', '03', '03', '022', 'KAPAL OSEANOGRAFI'),
			(877, '2', '02', '03', '03', '023', 'PERAHU TRADISIONAL'),
			(878, '2', '02', '03', '03', '024', 'SEA RIDER'),
			(879, '2', '02', '03', '03', '025', 'HOVER CRAFT'),
			(880, '2', '02', '03', '03', '026', 'KAPAL PENGANGKUT IKAN'),
			(881, '2', '02', '03', '03', '027', 'KAPAL PENGOLAH IKAN'),
			(882, '2', '02', '03', '03', '028', 'KAPAL PENELITIAN/ EKSPLORASI PERIKANAN'),
			(883, '2', '02', '03', '03', '029', 'KAPAL PENDUKUNG OPERASI PENANGKAPAN IKAN'),
			(884, '2', '02', '03', '03', '030', 'KAPAL PENDUKUNG OPERASI PEMBUDIDAYAAN IKAN'),
			(885, '2', '02', '03', '03', '031', 'KAPAL PENGAWAS PERIKANAN'),
			(886, '2', '02', '03', '03', '032', 'PERAHU INTAI 3 ORANG'),
			(887, '2', '02', '03', '03', '033', 'PERAHU SERBU 15 ORANG'),
			(888, '2', '02', '03', '03', '034', 'KAPAL PATROLI POLISI'),
			(889, '2', '02', '03', '03', '035', 'JET SKY'),
			(890, '2', '02', '03', '03', '999', 'ALAT ANGKUTAN APUNG BERMOTOR KHUSUS LAINNYA'),
			(891, '2', '02', '03', '99', '000', 'ALAT ANGKUTAN APUNG BERMOTOR LAINNYA'),
			(892, '2', '02', '03', '99', '999', 'ALAT ANGKUTAN APUNG BERMOTOR LAINNYA'),
			(893, '2', '02', '04', '00', '000', 'ALAT ANGKUTAN APUNG TAK BERMOTOR'),
			(894, '2', '02', '04', '01', '000', 'ALAT ANGKUTAN APUNG TAK BERMOTOR UNTUK BARANG'),
			(895, '2', '02', '04', '01', '001', 'TONGKANG'),
			(896, '2', '02', '04', '01', '002', 'PERAHU BARANG'),
			(897, '2', '02', '04', '01', '999', 'ALAT ANGKUTAN APUNG TAK BERMOTOR UNTUK BARANG LAINNYA'),
			(898, '2', '02', '04', '02', '000', 'ALAT ANGKUTAN APUNG TAK BERMOTOR UNTUK PENUMPANG'),
			(899, '2', '02', '04', '02', '001', 'PERAHU PENUMPANG'),
			(900, '2', '02', '04', '02', '002', 'PERAHU PENYEBERANGAN'),
			(901, '2', '02', '04', '02', '999', 'ALAT ANGKUTAN APUNG TAK BERMOTOR UNTUK PENUMPANG LAINNYA'),
			(902, '2', '02', '04', '03', '000', 'ALAT ANGKUTAN APUNG TAK BERMOTOR KHUSUS'),
			(903, '2', '02', '04', '03', '001', 'PONTON'),
			(904, '2', '02', '04', '03', '002', 'PERAHU KARET (ALAT ANGKUTAN APUNG TAK BERMOTOR KHUSUS)'),
			(905, '2', '02', '04', '03', '003', 'PONTON RUMAH'),
			(906, '2', '02', '04', '03', '004', 'FLOATING PLATFORM/RAKIT'),
			(907, '2', '02', '04', '03', '999', 'ALAT ANGKUTAN APUNG TAK BERMOTOR KHUSUS LAINNYA'),
			(908, '2', '02', '04', '99', '000', 'ALAT ANGKUTAN APUNG TAK BERMOTOR LAINNYA'),
			(909, '2', '02', '04', '99', '999', 'ALAT ANGKUTAN APUNG TAK BERMOTOR LAINNYA'),
			(910, '2', '03', '00', '00', '000', 'ALAT BENGKEL DAN ALAT UKUR'),
			(911, '2', '03', '01', '00', '000', 'ALAT BENGKEL BERMESIN'),
			(912, '2', '03', '01', '01', '000', 'PERKAKAS KONSTRUKSI LOGAM TERPASANG PADA PONDASI'),
			(913, '2', '03', '01', '01', '001', 'MESIN BUBUT'),
			(914, '2', '03', '01', '01', '002', 'MESIN FRAIS'),
			(915, '2', '03', '01', '01', '003', 'MESIN KETAM (PERKAKAS KONSTRUKSI LOGAM TERPASANG PADA PONDASI)'),
			(916, '2', '03', '01', '01', '004', 'MESIN PRESS HIDROLIK & PUNCH'),
			(917, '2', '03', '01', '01', '005', 'MESIN BOR'),
			(918, '2', '03', '01', '01', '006', 'MESIN GERGAJI LOGAM'),
			(919, '2', '03', '01', '01', '007', 'MESIN GERINDA'),
			(920, '2', '03', '01', '01', '008', 'MESIN ROL'),
			(921, '2', '03', '01', '01', '009', 'MESIN BOR CYLINDER'),
			(922, '2', '03', '01', '01', '010', 'MESIN SKRUP'),
			(923, '2', '03', '01', '01', '011', 'MESIN MEILING'),
			(924, '2', '03', '01', '01', '012', 'MESIN PUREL'),
			(925, '2', '03', '01', '01', '013', 'MESIN PERAPEN'),
			(926, '2', '03', '01', '01', '014', 'MESIN SIKAT KULIT'),
			(927, '2', '03', '01', '01', '015', 'MESIN PEMOTONG KULIT'),
			(928, '2', '03', '01', '01', '016', 'MESIN JAHIT KULIT'),
			(929, '2', '03', '01', '01', '017', 'MESIN PENGEPRES KULIT'),
			(930, '2', '03', '01', '01', '018', 'MESIN KOMPRESOR'),
			(931, '2', '03', '01', '01', '019', 'MESIN LAS LISTRIK'),
			(932, '2', '03', '01', '01', '020', 'MESIN DYNAMO KRON'),
			(933, '2', '03', '01', '01', '021', 'MESIN SIKAT BESI KRON'),
			(934, '2', '03', '01', '01', '022', 'MESIN PEMOTONG FIBERGLAS/POLIYSTER'),
			(935, '2', '03', '01', '01', '023', 'MESIN GULUNG LISTRIK'),
			(936, '2', '03', '01', '01', '024', 'MESIN PELUBANG (PERKAKAS KONSTRUKSI LOGAM TERPASANG PADA PONDASI)'),
			(937, '2', '03', '01', '01', '025', 'MESIN PENEKUK/LIPAT PLAT'),
			(938, '2', '03', '01', '01', '026', 'MESIN GUNTING PLAT'),
			(939, '2', '03', '01', '01', '027', 'MESIN PEMBENGKOK UNI'),
			(940, '2', '03', '01', '01', '028', 'MESIN AMPLAS PLAT'),
			(941, '2', '03', '01', '01', '029', 'MESIN PEMOTONG PLAT'),
			(942, '2', '03', '01', '01', '030', 'MESIN TRANSMISSION AUTOMOTIVE'),
			(943, '2', '03', '01', '01', '031', 'MESIN PEMBENGKOK LOGAM'),
			(944, '2', '03', '01', '01', '032', 'MESIN CRYSTAL GROWING'),
			(945, '2', '03', '01', '01', '033', 'MESIN LASER CUTTING'),
			(946, '2', '03', '01', '01', '034', 'MESIN LASER WELDING'),
			(947, '2', '03', '01', '01', '035', 'MESIN LIPAT PLAT'),
			(948, '2', '03', '01', '01', '036', 'MESIN BRIKET'),
			(949, '2', '03', '01', '01', '037', 'UNIV. GRINDER SETING VALVE'),
			(950, '2', '03', '01', '01', '038', 'UNIV. GRINDER VALVE REPAIR'),
			(951, '2', '03', '01', '01', '039', 'MESIN SERUT'),
			(952, '2', '03', '01', '01', '040', 'MESIN PROFILE KAYU'),
			(953, '2', '03', '01', '01', '999', 'PERKAKAS KONSTRUKSI LOGAM TERPASANG PADA PONDASI LAINNYA'),
			(954, '2', '03', '01', '02', '000', 'PERKAKAS KONSTRUKSI LOGAM YANG TRANSPORTABLE (BERPINDAH)'),
			(955, '2', '03', '01', '02', '001', 'MESIN GERINDA TANGAN'),
			(956, '2', '03', '01', '02', '002', 'MESIN BOR TANGAN'),
			(957, '2', '03', '01', '02', '003', 'MESIN CYLINDER'),
			(958, '2', '03', '01', '02', '004', 'RIVETING MACHINE'),
			(959, '2', '03', '01', '02', '005', 'MESIN GULUNG MANUAL'),
			(960, '2', '03', '01', '02', '006', 'MESIN AMPELAS TANGAN'),
			(961, '2', '03', '01', '02', '007', 'MESIN AMPELAS ROL KECIL'),
			(962, '2', '03', '01', '02', '008', 'MESIN GERGAJI BESI'),
			(963, '2', '03', '01', '02', '999', 'PERKAKAS KONSTRUKSI LOGAM YANG TRANSPORTABLE (BERPINDAH) LAINNYA'),
			(964, '2', '03', '01', '03', '000', 'PERKAKAS BENGKEL LISTRIK'),
			(965, '2', '03', '01', '03', '001', 'BATTERY CHARGE'),
			(966, '2', '03', '01', '03', '002', 'WINDER'),
			(967, '2', '03', '01', '03', '003', 'TRANSFORMATOR'),
			(968, '2', '03', '01', '03', '004', 'SOLDER LISTRIK'),
			(969, '2', '03', '01', '03', '005', 'SEDOTAN TIMAH LISTRIK'),
			(970, '2', '03', '01', '03', '006', 'ELECTRICAL DISCHARGE'),
			(971, '2', '03', '01', '03', '007', 'VERTICAL MACHINING CENTRE'),
			(972, '2', '03', '01', '03', '008', 'COPY MILLING'),
			(973, '2', '03', '01', '03', '009', 'SURFACE GRINDING PROTH'),
			(974, '2', '03', '01', '03', '010', 'CYDRICAL GRINDER YAM'),
			(975, '2', '03', '01', '03', '011', 'CAPACITY DIE CASTING'),
			(976, '2', '03', '01', '03', '012', 'HMC CINTINATI MILACRON'),
			(977, '2', '03', '01', '03', '013', 'ENGINE CYLINDER RESEARCH ENGINE'),
			(978, '2', '03', '01', '03', '014', 'VALVE SENSOR'),
			(979, '2', '03', '01', '03', '015', 'COORDINATE MEASURING MACHINES'),
			(980, '2', '03', '01', '03', '016', 'ENGINE COOLING SYSTEM'),
			(981, '2', '03', '01', '03', '017', 'OUTLET MANIFODLD PRESSURE'),
			(982, '2', '03', '01', '03', '018', 'IMPULSE ORBITAL WELDER'),
			(983, '2', '03', '01', '03', '019', 'AVL DIGAS'),
			(984, '2', '03', '01', '03', '020', 'ELECTRIC WIRE ROPE'),
			(985, '2', '03', '01', '03', '021', 'STEAM PRESSURE GAUGE'),
			(986, '2', '03', '01', '03', '022', 'SAVETUY VALVE'),
			(987, '2', '03', '01', '03', '023', 'TRESHER STATIS'),
			(988, '2', '03', '01', '03', '024', 'VARIAC'),
			(989, '2', '03', '01', '03', '025', 'MIXER (PERKAKAS BENGKEL LISTRIK)'),
			(990, '2', '03', '01', '03', '026', 'STEPPING MOTOR'),
			(991, '2', '03', '01', '03', '027', 'CYLINDER PRESSURE TRANDUCER'),
			(992, '2', '03', '01', '03', '028', 'ENGINE SIMULATION SOFTWARE PACKAGE'),
			(993, '2', '03', '01', '03', '029', 'AXHAUST GAS ANALIZER'),
			(994, '2', '03', '01', '03', '030', 'CIRCULAR SAW'),
			(995, '2', '03', '01', '03', '031', 'TESTER LISTRIK/TELEPON/INTERNET'),
			(996, '2', '03', '01', '03', '999', 'PERKAKAS BENGKEL LISTRIK LAINNYA (ALAT BENGKEL BERMESIN)'),
			(997, '2', '03', '01', '04', '000', 'PERKAKAS BENGKEL SERVICE'),
			(998, '2', '03', '01', '04', '001', 'AUTO LIFT'),
			(999, '2', '03', '01', '04', '002', 'CAR WASHER'),
			(1000, '2', '03', '01', '04', '003', 'STEAM CLEANER'),
			(1001, '2', '03', '01', '04', '004', 'LUBRIACATING EQUIPMENT'),
			(1002, '2', '03', '01', '04', '005', 'MESIN SPOORING'),
			(1003, '2', '03', '01', '04', '006', 'MESIN BALANCER'),
			(1004, '2', '03', '01', '04', '007', 'BRAKE DRUM LATHE/MESIN PERATA TROMOL'),
			(1005, '2', '03', '01', '04', '008', 'PENGASAH LUBANG STANG PISTON'),
			(1006, '2', '03', '01', '04', '009', 'LUBRICATING SET (PERKAKAS BENGKEL SERVICE)'),
			(1007, '2', '03', '01', '04', '010', 'AIR FILTER REGULATOR'),
			(1008, '2', '03', '01', '04', '011', 'DIAMOND CARE DRILL CARE'),
			(1009, '2', '03', '01', '04', '012', 'AC MOTOR CONTROL'),
			(1010, '2', '03', '01', '04', '999', 'PERKAKAS BENGKEL SERVICE LAINNYA (ALAT BENGKEL BERMESIN)'),
			(1011, '2', '03', '01', '05', '000', 'PERKAKAS PENGANGKAT BERMESIN'),
			(1012, '2', '03', '01', '05', '001', 'OVERHEAD CRANE'),
			(1013, '2', '03', '01', '05', '002', 'HOIST'),
			(1014, '2', '03', '01', '05', '003', 'WINCH/LIR'),
			(1015, '2', '03', '01', '05', '999', 'PERKAKAS PENGANGKAT BERMESIN LAINNYA'),
			(1016, '2', '03', '01', '06', '000', 'PERKAKAS BENGKEL KAYU'),
			(1017, '2', '03', '01', '06', '001', 'MESIN GERGAJI'),
			(1018, '2', '03', '01', '06', '002', 'MESIN KETAM (PERKAKAS BENGKEL KAYU)'),
			(1019, '2', '03', '01', '06', '003', 'MESIN BOR KAYU'),
			(1020, '2', '03', '01', '06', '004', 'MESIN PENGHALUS'),
			(1021, '2', '03', '01', '06', '005', 'TATAH LISTRIK OSCAR MK 361'),
			(1022, '2', '03', '01', '06', '006', 'PASAH LISTRIK MKC'),
			(1023, '2', '03', '01', '06', '007', 'PROFILE LISTRIK MKC'),
			(1024, '2', '03', '01', '06', '008', 'GRENDO DUDUK'),
			(1025, '2', '03', '01', '06', '009', 'GERGAJI BENGKOK ATS'),
			(1026, '2', '03', '01', '06', '010', 'AMPLAS LISTRIK GMT'),
			(1027, '2', '03', '01', '06', '011', 'GERGAJI CHAIN SAW'),
			(1028, '2', '03', '01', '06', '012', 'TABLE SAW 10 EASTCO'),
			(1029, '2', '03', '01', '06', '999', 'PERKAKAS BENGKEL KAYU LAINNYA'),
			(1030, '2', '03', '01', '07', '000', 'PERKAKAS BENGKEL KHUSUS'),
			(1031, '2', '03', '01', '07', '001', 'MESIN JAHIT TERPAL'),
			(1032, '2', '03', '01', '07', '002', 'PERKAKAS VULKANISIR BAN'),
			(1033, '2', '03', '01', '07', '003', 'PERKAKAS BONGKAR/PASANG BAN'),
			(1034, '2', '03', '01', '07', '004', 'MESIN TENUN TEKSTIL'),
			(1035, '2', '03', '01', '07', '005', 'MESIN CELUP (PERKAKAS BENGKEL KHUSUS)'),
			(1036, '2', '03', '01', '07', '006', 'PEMASANG BARU'),
			(1037, '2', '03', '01', '07', '007', 'MESIN TENUN JAHIT'),
			(1038, '2', '03', '01', '07', '999', 'PERKAKAS BENGKEL KHUSUS LAINNYA'),
			(1039, '2', '03', '01', '08', '000', 'PERALATAN LAS'),
			(1040, '2', '03', '01', '08', '001', 'PERALATAN LAS LISTRIK'),
			(1041, '2', '03', '01', '08', '002', 'PERALATAN LAS KARBIT'),
			(1042, '2', '03', '01', '08', '003', 'PERALATAN LAS GAS'),
			(1043, '2', '03', '01', '08', '999', 'PERALATAN LAS LAINNYA'),
			(1044, '2', '03', '01', '99', '000', 'ALAT BENGKEL BERMESIN LAINNYA'),
			(1045, '2', '03', '01', '99', '999', 'ALAT BENGKEL BERMESIN LAINNYA'),
			(1046, '2', '03', '02', '00', '000', 'ALAT BENGKEL TAK BERMESIN'),
			(1047, '2', '03', '02', '01', '000', 'PERKAKAS BENGKEL KONSTRUKSI LOGAM'),
			(1048, '2', '03', '02', '01', '001', 'PERKAKAS DAPUR TEMPA'),
			(1049, '2', '03', '02', '01', '002', 'PERKAKAS BANGKU KERJA'),
			(1050, '2', '03', '02', '01', '003', 'PERKAKAS PENGUKUR'),
			(1051, '2', '03', '02', '01', '004', 'PERKAKAS PENGECORAN LOGAM'),
			(1052, '2', '03', '02', '01', '005', 'R O L'),
			(1053, '2', '03', '02', '01', '006', 'PERKAKAS PEMOTONG PLAT'),
			(1054, '2', '03', '02', '01', '007', 'PERKAKAS PRESS HIDROLIK'),
			(1055, '2', '03', '02', '01', '008', 'PERKAKAS PEMOTONG KABEL SLING'),
			(1056, '2', '03', '02', '01', '009', 'PERKAKAS PENGECATAN KENDARAAN'),
			(1057, '2', '03', '02', '01', '999', 'PERKAKAS BENGKEL KONSTRUKSI LOGAM LAINNYA'),
			(1058, '2', '03', '02', '02', '000', 'PERKAKAS BENGKEL LISTRIK'),
			(1059, '2', '03', '02', '02', '001', 'ARMATURE DRYING OVEN'),
			(1060, '2', '03', '02', '02', '002', 'MICA UNDERCUTTER'),
			(1061, '2', '03', '02', '02', '003', 'COMMUTATOR TURNING TOOL'),
			(1062, '2', '03', '02', '02', '004', 'ARMATURE CROWLER'),
			(1063, '2', '03', '02', '02', '005', 'SOLID STATE SOLDERING GUN'),
			(1064, '2', '03', '02', '02', '999', 'PERKAKAS BENGKEL LISTRIK LAINNYA (ALAT BENGKEL TAK BERMESIN)'),
			(1065, '2', '03', '02', '03', '000', 'PERKAKAS BENGKEL SERVICE'),
			(1066, '2', '03', '02', '03', '001', 'PERKAKAS BENGKEL SERVICE'),
			(1067, '2', '03', '02', '03', '002', 'LUBRICATING SET (PERKAKAS BENGKEL SERVICE)'),
			(1068, '2', '03', '02', '03', '003', 'PERLENGKAPAN BENGKEL MEKANIK'),
			(1069, '2', '03', '02', '03', '004', 'JEMBATAN SERVICE HIDROLIK'),
			(1070, '2', '03', '02', '03', '999', 'PERKAKAS BENGKEL SERVICE LAINNYA (ALAT BENGKEL TAK BERMESIN)'),
			(1071, '2', '03', '02', '04', '000', 'PERKAKAS PENGANGKAT'),
			(1072, '2', '03', '02', '04', '001', 'DONGKRAK MEKANIK'),
			(1073, '2', '03', '02', '04', '002', 'DONGKRAK HIDROLIK'),
			(1074, '2', '03', '02', '04', '003', 'T A K E L'),
			(1075, '2', '03', '02', '04', '004', 'G A N T R Y'),
			(1076, '2', '03', '02', '04', '005', 'T R I P O D'),
			(1077, '2', '03', '02', '04', '006', 'FLOOR CRANE'),
			(1078, '2', '03', '02', '04', '999', 'PERKAKAS PENGANGKAT LAINNYA'),
			(1079, '2', '03', '02', '05', '000', 'PERKAKAS STANDARD (STANDARD TOOLS)'),
			(1080, '2', '03', '02', '05', '001', 'TOOL KIT SET'),
			(1081, '2', '03', '02', '05', '002', 'TOOL KIT BOX'),
			(1082, '2', '03', '02', '05', '003', 'TOOL CABINET SET'),
			(1083, '2', '03', '02', '05', '004', 'KUNCI PIPA'),
			(1084, '2', '03', '02', '05', '005', 'PULLER SET'),
			(1085, '2', '03', '02', '05', '006', 'TAP DIES'),
			(1086, '2', '03', '02', '05', '007', 'GREEPER'),
			(1087, '2', '03', '02', '05', '008', 'ENGINE STAND'),
			(1088, '2', '03', '02', '05', '009', 'KUNCI MOMENT'),
			(1089, '2', '03', '02', '05', '010', 'PEMBUAT FISIK (DIESS)'),
			(1090, '2', '03', '02', '05', '011', 'TUNGKU NON FERROUS'),
			(1091, '2', '03', '02', '05', '012', 'WHEEL CHOCK (PERKAKAS STANDARD (STANDARD TOOLS))'),
			(1092, '2', '03', '02', '05', '013', 'MAINTENANCE STEP'),
			(1093, '2', '03', '02', '05', '014', 'CRIMPING TOLLS'),
			(1094, '2', '03', '02', '05', '015', 'TOOLKIT TUKANG KAYU TON'),
			(1095, '2', '03', '02', '05', '016', 'TOOLKIT TUKANG BATU TON'),
			(1096, '2', '03', '02', '05', '017', 'TOOLKIT TUKANG LISTRIK'),
			(1097, '2', '03', '02', '05', '018', 'TOOLKIT PEMELIHARAAN'),
			(1098, '2', '03', '02', '05', '019', 'TOOLKIT PERBENGKELAN'),
			(1099, '2', '03', '02', '05', '020', 'TOOLKIT PERPIPAAN'),
			(1100, '2', '03', '02', '05', '021', 'TOOL OUTFIT PIONER ELECTRIC'),
			(1101, '2', '03', '02', '05', '022', 'TOOL GENERAL MECHANIC SET'),
			(1102, '2', '03', '02', '05', '023', 'TOOLKIT TUKANG BESI'),
			(1103, '2', '03', '02', '05', '024', 'TOOL ELECTRICAL SET'),
			(1104, '2', '03', '02', '05', '025', 'SAWMIL'),
			(1105, '2', '03', '02', '05', '026', 'UNIT PELUMAS PORTABLE'),
			(1106, '2', '03', '02', '05', '027', 'SCAFOLDING SET & TOOL'),
			(1107, '2', '03', '02', '05', '028', 'HAND FALLET'),
			(1108, '2', '03', '02', '05', '029', 'PARON'),
			(1109, '2', '03', '02', '05', '030', 'CYLINDER BEARING'),
			(1110, '2', '03', '02', '05', '031', 'PERLENGKAPAN BENGKEL PENGECATAN'),
			(1111, '2', '03', '02', '05', '999', 'PERKAKAS STANDARD (STANDARD TOOLS) LAINNYA'),
			(1112, '2', '03', '02', '06', '000', 'PERKAKAS KHUSUS (SPECIAL TOOLS)'),
			(1113, '2', '03', '02', '06', '001', 'KUNCI KHUSUS UNTUK ENGINE'),
			(1114, '2', '03', '02', '06', '002', 'KUNCI KHUSUS ALAT BESAR DARAT'),
			(1115, '2', '03', '02', '06', '003', 'KUNCI KHUSUS ALAT BESAR APUNG'),
			(1116, '2', '03', '02', '06', '004', 'KUNCI KHUSUS CASIS ALAT ANGKUT DARAT'),
			(1117, '2', '03', '02', '06', '005', 'KUNCI KHUSUS CASIS'),
			(1118, '2', '03', '02', '06', '006', 'KUNCI KHUSUS ALAT ANGKUT APUNG'),
			(1119, '2', '03', '02', '06', '007', 'KUNCI KHUSUS PEMBUKA MUR/BAUT'),
			(1120, '2', '03', '02', '06', '008', 'KUNCI KHUSUS MOMENT'),
			(1121, '2', '03', '02', '06', '009', 'KUNCI KHUSUS ALAT BESAR UDARA'),
			(1122, '2', '03', '02', '06', '010', 'KUNCI KHUSUS CASIS ALAT BESAR UDARA'),
			(1123, '2', '03', '02', '06', '011', 'DIGITAL TANG AMPERE'),
			(1124, '2', '03', '02', '06', '012', 'DIGITAL TACHOMETER'),
			(1125, '2', '03', '02', '06', '013', 'FOOT KLEP'),
			(1126, '2', '03', '02', '06', '014', 'CINCIN/KOPLING SLANG HYDRANT'),
			(1127, '2', '03', '02', '06', '015', 'KUNCI L'),
			(1128, '2', '03', '02', '06', '016', 'TBA'),
			(1129, '2', '03', '02', '06', '999', 'PERKAKAS KHUSUS (SPECIAL TOOLS) LAINNYA'),
			(1130, '2', '03', '02', '07', '000', 'PERKAKAS BENGKEL KERJA'),
			(1131, '2', '03', '02', '07', '001', 'GERGAJI'),
			(1132, '2', '03', '02', '07', '002', 'KETAM'),
			(1133, '2', '03', '02', '07', '003', 'BOR'),
			(1134, '2', '03', '02', '07', '004', 'PAHAT'),
			(1135, '2', '03', '02', '07', '005', 'KAKAK TUA'),
			(1136, '2', '03', '02', '07', '006', 'WATER PAS'),
			(1137, '2', '03', '02', '07', '007', 'SIKU'),
			(1138, '2', '03', '02', '07', '008', 'PALU'),
			(1139, '2', '03', '02', '07', '999', 'PERKAKAS BENGKEL KERJA LAINNYA'),
			(1140, '2', '03', '02', '08', '000', 'PERALATAN TUKANG BESI'),
			(1141, '2', '03', '02', '08', '001', 'TANGGEM'),
			(1142, '2', '03', '02', '08', '002', 'GUNTING PLAT'),
			(1143, '2', '03', '02', '08', '003', 'LANDASAN KENTENG'),
			(1144, '2', '03', '02', '08', '004', 'KUNCI KAUL'),
			(1145, '2', '03', '02', '08', '005', 'GUNTING PLAT TANGAN'),
			(1146, '2', '03', '02', '08', '006', 'TANG KOMBINASI'),
			(1147, '2', '03', '02', '08', '007', 'TANG POTONG'),
			(1148, '2', '03', '02', '08', '008', '\"BETEL'),
			(1149, '2', '03', '02', '08', '009', 'PUKUL KONDE'),
			(1150, '2', '03', '02', '08', '010', 'PUKUL LENGKUNG'),
			(1151, '2', '03', '02', '08', '011', 'PUKUL SABIT'),
			(1152, '2', '03', '02', '08', '012', 'KIKIR'),
			(1153, '2', '03', '02', '08', '013', 'KUNCI PAS'),
			(1154, '2', '03', '02', '08', '014', 'TANG SENAI & TAP'),
			(1155, '2', '03', '02', '08', '015', 'DREI BIASA (OBENG)'),
			(1156, '2', '03', '02', '08', '016', 'DREI KEMBANG (OBENG)'),
			(1157, '2', '03', '02', '08', '017', 'DREI KETOK (OBENG)'),
			(1158, '2', '03', '02', '08', '018', 'SEKET MAT'),
			(1159, '2', '03', '02', '08', '019', 'JANGKA BESI'),
			(1160, '2', '03', '02', '08', '020', 'KUNCI STANG'),
			(1161, '2', '03', '02', '08', '999', 'PERALATAN TUKANG BESI LAINNYA'),
			(1162, '2', '03', '02', '09', '000', 'PERALATAN TUKANG KAYU'),
			(1163, '2', '03', '02', '09', '001', 'TATAH BIASA'),
			(1164, '2', '03', '02', '09', '002', 'TATAH LENGKUNG'),
			(1165, '2', '03', '02', '09', '003', 'KAOTA'),
			(1166, '2', '03', '02', '09', '004', 'PETEL'),
			(1167, '2', '03', '02', '09', '005', 'PATAR'),
			(1168, '2', '03', '02', '09', '006', 'BOR ENGKOL'),
			(1169, '2', '03', '02', '09', '007', 'PERLENGKAPAN BENGKEL KAYU'),
			(1170, '2', '03', '02', '09', '999', 'PERALATAN TUKANG KAYU LAINNYA'),
			(1171, '2', '03', '02', '10', '000', 'PERALATAN TUKANG KULIT'),
			(1172, '2', '03', '02', '10', '001', 'PISAU KULIT'),
			(1173, '2', '03', '02', '10', '002', 'PANDOKAN SEPATU'),
			(1174, '2', '03', '02', '10', '003', 'LIS SEPATU'),
			(1175, '2', '03', '02', '10', '004', 'COKRO'),
			(1176, '2', '03', '02', '10', '005', 'PLONG KULIT'),
			(1177, '2', '03', '02', '10', '006', 'CATUT'),
			(1178, '2', '03', '02', '10', '007', 'PUKUL SEPATU'),
			(1179, '2', '03', '02', '10', '008', 'GUNTING KULIT'),
			(1180, '2', '03', '02', '10', '009', 'GUNTING KAIN'),
			(1181, '2', '03', '02', '10', '010', 'DREK MATA AYAM'),
			(1182, '2', '03', '02', '10', '012', 'UNCEK'),
			(1183, '2', '03', '02', '10', '999', 'PERALATAN TUKANG KULIT LAINNYA'),
			(1184, '2', '03', '02', '11', '000', '\"PERALATAN UKUR'),
			(1185, '2', '03', '02', '11', '001', 'DIPAN UKUR'),
			(1186, '2', '03', '02', '11', '002', 'METERAN KAIN'),
			(1187, '2', '03', '02', '11', '003', 'ROL METER'),
			(1188, '2', '03', '02', '11', '004', 'JANGKA BERKAKI'),
			(1189, '2', '03', '02', '11', '005', 'PATAR GIP'),
			(1190, '2', '03', '02', '11', '006', 'PISAU GIP'),
			(1191, '2', '03', '02', '11', '007', 'PARAREL BAR'),
			(1192, '2', '03', '02', '11', '008', 'CERMIN BESAR'),
			(1193, '2', '03', '02', '11', '009', 'TANGGA LATIHAN'),
			(1194, '2', '03', '02', '11', '010', 'TRAP LATIHAN'),
			(1195, '2', '03', '02', '11', '999', '\"PERALATAN UKUR'),
			(1196, '2', '03', '02', '12', '000', 'PERALATAN BENGKEL KHUSUS PELADAM'),
			(1197, '2', '03', '02', '12', '001', 'MESIN CNC'),
			(1198, '2', '03', '02', '12', '002', 'DYNAMO TUNGKU'),
			(1199, '2', '03', '02', '12', '003', 'MESIN FRAIS'),
			(1200, '2', '03', '02', '12', '004', 'MESIN SKRAF'),
			(1201, '2', '03', '02', '12', '005', 'MESIN BOR MEJA / KAKI LISTRIK'),
			(1202, '2', '03', '02', '12', '006', 'PALU BESAR'),
			(1203, '2', '03', '02', '12', '007', 'MESIN KORTER'),
			(1204, '2', '03', '02', '12', '008', 'PALU KECIL'),
			(1205, '2', '03', '02', '12', '009', 'MESIN GERINDA DUDUK (BENCH GERINDA)'),
			(1206, '2', '03', '02', '12', '010', 'GEGEP PEMOTONG KUKU'),
			(1207, '2', '03', '02', '12', '011', 'GEGEP PEMOTONG PAKU'),
			(1208, '2', '03', '02', '12', '012', 'PISAU RENET'),
			(1209, '2', '03', '02', '12', '013', 'MESIN JAHIT TERPAL'),
			(1210, '2', '03', '02', '12', '014', 'PELOBANG TAPEL'),
			(1211, '2', '03', '02', '12', '015', 'TANG BUAYA'),
			(1212, '2', '03', '02', '12', '016', 'MESIN BATTERY SET / PENGISI ACCU'),
			(1213, '2', '03', '02', '12', '017', 'PERALATAN BENGKEL LAINNYA'),
			(1214, '2', '03', '02', '12', '018', 'MESIN BLOWER LISTRIK / MEKANIK'),
			(1215, '2', '03', '02', '12', '019', 'MESIN SIKAT / BRUSH MACHINE'),
			(1216, '2', '03', '02', '12', '020', 'MESIN PEMBUKA BAN'),
			(1217, '2', '03', '02', '12', '021', 'MESIN SLEP KRUK AS'),
			(1218, '2', '03', '02', '12', '022', 'MESIN ASAH SILIDER COP'),
			(1219, '2', '03', '02', '12', '023', 'MESIN GULUNG SPOOL'),
			(1220, '2', '03', '02', '12', '024', 'MESIN GULUNG PLAT'),
			(1221, '2', '03', '02', '12', '025', 'MESIN POMPA AIR PMK'),
			(1222, '2', '03', '02', '12', '026', 'MESIN ASAH KLEP'),
			(1223, '2', '03', '02', '12', '027', 'MESIN TUSUK / STIK'),
			(1224, '2', '03', '02', '12', '028', 'MESIN BOR LISTRIK TANGAN'),
			(1225, '2', '03', '02', '12', '029', 'MESIN NIMBLING'),
			(1226, '2', '03', '02', '12', '030', 'MESIN GERINDA TANGAN LISTRIK'),
			(1227, '2', '03', '02', '12', '031', 'MESIN POTONG PLAT BENTUK / HAND NIMBLER'),
			(1228, '2', '03', '02', '12', '032', 'UNIT CAT'),
			(1229, '2', '03', '02', '12', '033', 'CUT OFF SAW'),
			(1230, '2', '03', '02', '12', '034', 'MESIN ANALISA SYSTEM'),
			(1231, '2', '03', '02', '12', '035', 'BLENDER LAS POTONG'),
			(1232, '2', '03', '02', '12', '036', 'MESIN CUCI KENDARAAN/ CAR WASHER'),
			(1233, '2', '03', '02', '12', '037', 'PERKAKAS AC'),
			(1234, '2', '03', '02', '12', '999', 'PERALATAN BENGKEL KHUSUS PELADAM LAINNYA'),
			(1235, '2', '03', '02', '99', '000', 'ALAT BENGKEL TAK BERMESIN LAINNYA'),
			(1236, '2', '03', '02', '99', '999', 'ALAT BENGKEL TAK BERMESIN LAINNYA'),
			(1237, '2', '03', '03', '00', '000', 'ALAT UKUR'),
			(1238, '2', '03', '03', '01', '000', 'ALAT UKUR UNIVERSAL'),
			(1239, '2', '03', '03', '01', '001', 'AF GENERATOR TONE GENERATOR'),
			(1240, '2', '03', '03', '01', '002', 'AUDIO SIGNAL SOURCE'),
			(1241, '2', '03', '03', '01', '003', 'AUDIO TEST SET'),
			(1242, '2', '03', '03', '01', '004', 'AUDIO MORSE & DISTRIBUTOR METER'),
			(1243, '2', '03', '03', '01', '005', 'AUDIO SWEEP OSILATOR'),
			(1244, '2', '03', '03', '01', '006', 'VTVM VOLT'),
			(1245, '2', '03', '03', '01', '007', 'INDEPENDENCE METER'),
			(1246, '2', '03', '03', '01', '008', 'DECIBLE METER'),
			(1247, '2', '03', '03', '01', '009', 'CRT TESTER'),
			(1248, '2', '03', '03', '01', '010', 'CIRCUIT TESTER (ALAT UKUR UNIVERSAL)'),
			(1249, '2', '03', '03', '01', '011', 'ELECTRONIC CAPASITOR TESTER'),
			(1250, '2', '03', '03', '01', '012', 'ILLUMINO METER'),
			(1251, '2', '03', '03', '01', '013', 'IC TESTER SEMI TEST IV'),
			(1252, '2', '03', '03', '01', '014', 'IC METER'),
			(1253, '2', '03', '03', '01', '015', 'MIHVOLT METER'),
			(1254, '2', '03', '03', '01', '016', 'MULTITESTER & ACCESSORIE'),
			(1255, '2', '03', '03', '01', '017', 'MULTISESTER DIGITAL'),
			(1256, '2', '03', '03', '01', '018', 'PHOTO ILLUMINATION METER'),
			(1257, '2', '03', '03', '01', '019', 'TRANSISTOR TESTER SEMITEST I'),
			(1258, '2', '03', '03', '01', '020', 'TRANSISTOR TESTER SEMITEST II'),
			(1259, '2', '03', '03', '01', '021', 'TRANSISTOR TESTER SEMITEST V'),
			(1260, '2', '03', '03', '01', '022', 'TRANSISTOR TESTER AVO'),
			(1261, '2', '03', '03', '01', '023', 'VOLT METER ELEKTRONIK'),
			(1262, '2', '03', '03', '01', '024', 'VOLT METER DIGITAL'),
			(1263, '2', '03', '03', '01', '025', 'VOLT METER HIGT TENSION'),
			(1264, '2', '03', '03', '01', '026', 'WIDW BAND LEVEL METER'),
			(1265, '2', '03', '03', '01', '027', 'AUTOMATIC DISTROTION METER'),
			(1266, '2', '03', '03', '01', '028', 'POWER METER AND ACCESSORIES'),
			(1267, '2', '03', '03', '01', '029', 'PH METER (ALAT UKUR UNIVERSAL)'),
			(1268, '2', '03', '03', '01', '030', 'QUASI PEAK METER'),
			(1269, '2', '03', '03', '01', '031', 'THRULINE WATT METER'),
			(1270, '2', '03', '03', '01', '032', 'DIGITAL MULTIMETER (ALAT UKUR UNIVERSAL)'),
			(1271, '2', '03', '03', '01', '033', 'MULTI METER'),
			(1272, '2', '03', '03', '01', '034', 'METER CALIBRATOR'),
			(1273, '2', '03', '03', '01', '035', 'MOISE FIGURE METER'),
			(1274, '2', '03', '03', '01', '036', 'DISTORTION ANALYZER'),
			(1275, '2', '03', '03', '01', '037', 'VECTOR VOLT METER (ALAT UKUR UNIVERSAL)'),
			(1276, '2', '03', '03', '01', '038', 'PULSE GENERATOR (ALAT UKUR UNIVERSAL)'),
			(1277, '2', '03', '03', '01', '039', 'DME GROUND STATION TEST SET (ALAT UKUR UNIVERSAL)'),
			(1278, '2', '03', '03', '01', '040', 'UHF SIGNAL GENERATOR'),
			(1279, '2', '03', '03', '01', '041', 'SWEEP OSCILLATOR (ALAT UKUR UNIVERSAL)'),
			(1280, '2', '03', '03', '01', '042', 'VHF SIGNAL GENERATOR'),
			(1281, '2', '03', '03', '01', '043', 'SPEKTRUM ANALYZER'),
			(1282, '2', '03', '03', '01', '044', 'TUBE TESTER (ALAT UKUR UNIVERSAL)'),
			(1283, '2', '03', '03', '01', '045', 'DOSIMETER & ACCESORIES'),
			(1284, '2', '03', '03', '01', '046', 'SURVEY METER (ALAT UKUR UNIVERSAL)'),
			(1285, '2', '03', '03', '01', '047', 'SOUND DETECTOR'),
			(1286, '2', '03', '03', '01', '048', 'VIDICON QUICK TESTER'),
			(1287, '2', '03', '03', '01', '049', 'PATTERN FOR TV ADJUSTMENT'),
			(1288, '2', '03', '03', '01', '050', 'POWER METER CILLIBRATOR'),
			(1289, '2', '03', '03', '01', '051', 'THERMISTOR'),
			(1290, '2', '03', '03', '01', '052', '\"SIGNAL GENERATOR AUDIO VHF'),
			(1291, '2', '03', '03', '01', '053', 'X - TAL DETECTOR'),
			(1292, '2', '03', '03', '01', '054', 'CO - AXIAL SLOT LINE'),
			(1293, '2', '03', '03', '01', '055', 'RF VOLT METER'),
			(1294, '2', '03', '03', '01', '056', 'FREKQUENCY WAVE METER'),
			(1295, '2', '03', '03', '01', '057', 'MEGGER'),
			(1296, '2', '03', '03', '01', '058', 'CO AXIAL ATTENUATOR'),
			(1297, '2', '03', '03', '01', '059', 'VARIABEL CO AXIAL ATTENUATOR'),
			(1298, '2', '03', '03', '01', '060', 'DIRECTIONAL COUPLER (ALAT UKUR UNIVERSAL)'),
			(1299, '2', '03', '03', '01', '061', 'PIN MODULATOR'),
			(1300, '2', '03', '03', '01', '062', 'LOGIG TROUBLE SHOTING KIT'),
			(1301, '2', '03', '03', '01', '063', 'SWR METER'),
			(1302, '2', '03', '03', '01', '064', 'MEMORI PROGRAMMER'),
			(1303, '2', '03', '03', '01', '065', 'LOGIG STATC ANALYZER'),
			(1304, '2', '03', '03', '01', '066', 'FREQUENCY CUONTER'),
			(1305, '2', '03', '03', '01', '067', 'UNIVERSAL BRIDGE'),
			(1306, '2', '03', '03', '01', '068', 'FB METER'),
			(1307, '2', '03', '03', '01', '069', 'NOISE'),
			(1308, '2', '03', '03', '01', '070', 'RADIATION MONITOR ISOTROPIC'),
			(1309, '2', '03', '03', '01', '071', 'PHASE METER'),
			(1310, '2', '03', '03', '01', '072', 'GLOBAL POSITIONING SYSTEM'),
			(1311, '2', '03', '03', '01', '073', 'ILS. CALIBRATION RX.'),
			(1312, '2', '03', '03', '01', '074', 'DCP ( ALAT CONTROL ) SENSOR'),
			(1313, '2', '03', '03', '01', '075', 'MOISTEUR METER'),
			(1314, '2', '03', '03', '01', '076', 'ROTA METER'),
			(1315, '2', '03', '03', '01', '077', 'MINI PHASEC VIEW'),
			(1316, '2', '03', '03', '01', '078', 'FREQUENCY INVERTER'),
			(1317, '2', '03', '03', '01', '079', 'ACCUMETER'),
			(1318, '2', '03', '03', '01', '080', 'TEMPERATUR DIGITAL'),
			(1319, '2', '03', '03', '01', '081', 'ARGOMETER'),
			(1320, '2', '03', '03', '01', '082', 'DIAL TEST INDICATOR'),
			(1321, '2', '03', '03', '01', '083', 'SPEED METER'),
			(1322, '2', '03', '03', '01', '084', '\"OIL BATH'),
			(1323, '2', '03', '03', '01', '085', 'SPEED DETECTOR'),
			(1324, '2', '03', '03', '01', '086', 'THERMOHYGROMETER (ALAT UKUR UNIVERSAL)'),
			(1325, '2', '03', '03', '01', '087', 'TRAFFIC COUNTER'),
			(1326, '2', '03', '03', '01', '088', 'STANDAR TEST GAUGE'),
			(1327, '2', '03', '03', '01', '090', 'SIGMA METER'),
			(1328, '2', '03', '03', '01', '091', 'IONISASI METER'),
			(1329, '2', '03', '03', '01', '092', 'ROTAN SAMPLER SPLITER'),
			(1330, '2', '03', '03', '01', '093', 'HENRY METER'),
			(1331, '2', '03', '03', '01', '094', 'MESIN KOCOK HORISONTAL'),
			(1332, '2', '03', '03', '01', '095', 'CAPASITOR METER'),
			(1333, '2', '03', '03', '01', '096', 'MICROPROCESSOR CONDUCTIVITY'),
			(1334, '2', '03', '03', '01', '097', 'UHF OUT PUSTTESSTING EQUIPMENT'),
			(1335, '2', '03', '03', '01', '098', 'SHRANGKAGE LIMIT APPARATUS'),
			(1336, '2', '03', '03', '01', '099', 'R.F. SIGNAL GENERATOR'),
			(1337, '2', '03', '03', '01', '100', 'DEWMETER PRINT'),
			(1338, '2', '03', '03', '01', '102', 'ORBITAL SHAKER'),
			(1339, '2', '03', '03', '01', '103', 'VHF/UHF DUMMY LOAD'),
			(1340, '2', '03', '03', '01', '104', 'OZONIZER'),
			(1341, '2', '03', '03', '01', '105', 'PSOPHOMETRIC WEIGHTING NETWORK'),
			(1342, '2', '03', '03', '01', '106', 'PERSONAL CDT'),
			(1343, '2', '03', '03', '01', '107', 'PORTABLE TEST RECK'),
			(1344, '2', '03', '03', '01', '108', 'RADIO METER (ALAT UKUR UNIVERSAL)'),
			(1345, '2', '03', '03', '01', '109', 'NMOTOR DRIVE WIRE WROPPER'),
			(1346, '2', '03', '03', '01', '110', 'SALINITY TEMP DEPTH ANALIZER'),
			(1347, '2', '03', '03', '01', '111', 'DIGITAL CIRCUIT TESTER'),
			(1348, '2', '03', '03', '01', '112', 'SALINOMETER'),
			(1349, '2', '03', '03', '01', '113', 'FIELD STRENGTH METER'),
			(1350, '2', '03', '03', '01', '114', 'ACIENTIFIC SOUNDEER SYSTEM'),
			(1351, '2', '03', '03', '01', '115', 'ALTERNEATUR'),
			(1352, '2', '03', '03', '01', '116', 'SENTER BAWAH AIR'),
			(1353, '2', '03', '03', '01', '117', 'MEGA OHM TESTER'),
			(1354, '2', '03', '03', '01', '118', 'SIX PLACE HIDROMANIFOLD'),
			(1355, '2', '03', '03', '01', '119', 'INSULATION TESTER (ALAT UKUR UNIVERSAL)'),
			(1356, '2', '03', '03', '01', '120', 'SONICATOR VIRSOIC CALL DISLUPTOR'),
			(1357, '2', '03', '03', '01', '121', 'ELECTRIC BENCH'),
			(1358, '2', '03', '03', '01', '122', 'SWEEP FUNCTION GENERATOR'),
			(1359, '2', '03', '03', '01', '123', 'LOADMETER'),
			(1360, '2', '03', '03', '01', '124', 'SYSTEM UV STERELISASI DAN SIRKULASI AI'),
			(1361, '2', '03', '03', '01', '125', 'COUNTER TESTER'),
			(1362, '2', '03', '03', '01', '126', 'SYSTEM FOR CHEMICAL OXYGEN DEMOND'),
			(1363, '2', '03', '03', '01', '127', 'THE DACOR SEASPRINT UNDER WATER VEHICLE'),
			(1364, '2', '03', '03', '01', '128', 'TITRATION UNIT'),
			(1365, '2', '03', '03', '01', '129', 'ULTRASONIC CLEANER (ALAT UKUR UNIVERSAL)'),
			(1366, '2', '03', '03', '01', '130', 'WATER ANALYSIS KIT'),
			(1367, '2', '03', '03', '01', '131', 'WHEEL METER'),
			(1368, '2', '03', '03', '01', '132', 'PROYECTION POLARISCOPE'),
			(1369, '2', '03', '03', '01', '133', 'CDMA/GSM TEST'),
			(1370, '2', '03', '03', '01', '134', 'ANTENNA SELECTOR'),
			(1371, '2', '03', '03', '01', '135', 'LOG PERIODIC ANTENNA'),
			(1372, '2', '03', '03', '01', '136', 'ALAT UKUR SIGMAT'),
			(1373, '2', '03', '03', '01', '999', 'ALAT UKUR UNIVERSAL LAINNYA'),
			(1374, '2', '03', '03', '02', '000', 'UNIVERSAL TESTER'),
			(1375, '2', '03', '03', '02', '001', 'FREQUENCY COUNTER (UNIVERSAL TESTER)'),
			(1376, '2', '03', '03', '02', '002', 'INSULATION RES METER MOD'),
			(1377, '2', '03', '03', '02', '003', 'NOISE & DISTORTION METER'),
			(1378, '2', '03', '03', '02', '004', 'OSCILATOR DISTORTION METER'),
			(1379, '2', '03', '03', '02', '005', 'OSCILATOR TEST SIGNAL'),
			(1380, '2', '03', '03', '02', '006', 'OSCILATOR WIDW BAND'),
			(1381, '2', '03', '03', '02', '007', 'OSCILATOR SWEEP'),
			(1382, '2', '03', '03', '02', '008', 'PRECISION ENCODER MONITOR'),
			(1383, '2', '03', '03', '02', '009', 'PLAMBICON TEST UNIT'),
			(1384, '2', '03', '03', '02', '010', 'SCANNER (UNIVERSAL TESTER)'),
			(1385, '2', '03', '03', '02', '011', 'TIME INTERVAL UNIT'),
			(1386, '2', '03', '03', '02', '012', 'UNIVERSAL COUNTER (UNIVERSAL TESTER)'),
			(1387, '2', '03', '03', '02', '013', 'VIDEO NOISE METER'),
			(1388, '2', '03', '03', '02', '014', 'ADMINTANCE METER'),
			(1389, '2', '03', '03', '02', '015', 'ADMINTANCE BRIDE'),
			(1390, '2', '03', '03', '02', '016', 'FIELDSTRENGTH METER'),
			(1391, '2', '03', '03', '02', '017', 'RF BRIDGE'),
			(1392, '2', '03', '03', '02', '018', 'RF PUSH BUTTON ATTENUATOR'),
			(1393, '2', '03', '03', '02', '019', 'VISION AND SOUND NYQUIST DEMODULATOR AMF'),
			(1394, '2', '03', '03', '02', '020', 'V.S.W.R STANDING REVIEW'),
			(1395, '2', '03', '03', '02', '022', 'DIGITAL FREQUENCE METER'),
			(1396, '2', '03', '03', '02', '023', 'VINDICAM QUICK TESTER'),
			(1397, '2', '03', '03', '02', '024', 'COAXIAL ATT'),
			(1398, '2', '03', '03', '02', '025', 'VARIABLE COAXIAL ATT'),
			(1399, '2', '03', '03', '02', '026', 'LOGIC PROBE (UNIVERSAL TESTER)'),
			(1400, '2', '03', '03', '02', '027', 'SURVEY METER (UNIVERSAL TESTER)'),
			(1401, '2', '03', '03', '02', '028', 'LOGIC COMPARATOR'),
			(1402, '2', '03', '03', '02', '999', 'UNIVERSAL TESTER LAINNYA'),
			(1403, '2', '03', '03', '03', '000', 'ALAT UKUR/PEMBANDING'),
			(1404, '2', '03', '03', '03', '001', 'UKURAN JOHANSON (ALAT PEMBANDING STANDAR UKURAN PANJANG)'),
			(1405, '2', '03', '03', '03', '002', 'MICRO INDICATOR (DENGAN PERLENGKAPAN SUPARTO POINTERS DAN REVOLV'),
			(1406, '2', '03', '03', '03', '003', 'PERLENGKAPAN MICRO INDICATOR'),
			(1407, '2', '03', '03', '03', '004', 'PSYCOMETER VANLAMBRECHT'),
			(1408, '2', '03', '03', '03', '005', 'PSYCOMETER'),
			(1409, '2', '03', '03', '03', '006', 'BAROMETER LOGAM'),
			(1410, '2', '03', '03', '03', '007', 'BAROMETER MERCURY'),
			(1411, '2', '03', '03', '03', '008', 'MANOMETER UNTUK MESIN'),
			(1412, '2', '03', '03', '03', '009', 'MONOTOR PRECISI'),
			(1413, '2', '03', '03', '03', '010', 'ALAT PEMERIKSA MANOMETER ( DENGAN PERLENGKAPAN )'),
			(1414, '2', '03', '03', '03', '011', 'ALAT PEMERIKSAAN ZAT CAIR'),
			(1415, '2', '03', '03', '03', '012', 'TERMOMETER STANDAR'),
			(1416, '2', '03', '03', '03', '013', 'TERMOMETER GOVERMEN TESTER 0 DERAJAT SAMPAI DENGAN 100 DERAJAT C'),
			(1417, '2', '03', '03', '03', '014', 'THERMOSTAT ( PENGUJI PEMERIKSAAN TERMOMETER )'),
			(1418, '2', '03', '03', '03', '015', 'JAM UKUR ( MEET LOCK )'),
			(1419, '2', '03', '03', '03', '016', 'HARDNES TESTER'),
			(1420, '2', '03', '03', '03', '017', 'STOPWATCH'),
			(1421, '2', '03', '03', '03', '018', 'LOUP'),
			(1422, '2', '03', '03', '03', '019', 'PLANIMETER (ALAT UKUR/PEMBANDING)'),
			(1423, '2', '03', '03', '03', '020', 'METRA BLOCK'),
			(1424, '2', '03', '03', '03', '021', 'LEMARI BAJA PENGERING'),
			(1425, '2', '03', '03', '03', '022', 'SANBLAS UNIT'),
			(1426, '2', '03', '03', '03', '023', 'ALAT PEMERIKSAAN TIMBANGAN TEKANAN BERODA'),
			(1427, '2', '03', '03', '03', '024', 'STELAN INSTRUMEN BOURJE'),
			(1428, '2', '03', '03', '03', '025', 'LAMPU UNTUK MENERANGI SKALA NERACA PAKAI STANDAR'),
			(1429, '2', '03', '03', '03', '026', 'AVOMETER SU 20 - 20 K'),
			(1430, '2', '03', '03', '03', '027', 'TRAPPO 1.000 WATT'),
			(1431, '2', '03', '03', '03', '028', 'TOOL SET'),
			(1432, '2', '03', '03', '03', '029', 'LANDASAN CAP LENGKAP'),
			(1433, '2', '03', '03', '03', '030', 'KAKI TIGA GANTUNGAN DACIN'),
			(1434, '2', '03', '03', '03', '031', 'ALAT PENDATAR TAKARAN BENSIN'),
			(1435, '2', '03', '03', '03', '032', 'TANG PLOMBIR / SEGEL'),
			(1436, '2', '03', '03', '03', '033', 'EXICATOR BESAR'),
			(1437, '2', '03', '03', '03', '034', 'EXICATOR KECIL'),
			(1438, '2', '03', '03', '03', '035', 'DESICATOR ( SIZE ) 3'),
			(1439, '2', '03', '03', '03', '036', 'DESICATOR ( SIZE ) 4'),
			(1440, '2', '03', '03', '03', '037', 'BOTOL AIR SALING DARI 25 LITER'),
			(1441, '2', '03', '03', '03', '038', 'PICNOMETER'),
			(1442, '2', '03', '03', '03', '039', 'DESIMETER ( HIDROMETER )'),
			(1443, '2', '03', '03', '03', '040', 'TELESCOPE TILE VARIEBLE'),
			(1444, '2', '03', '03', '03', '041', 'OPTICAL STREAN ( UNTUK PEMERIKSAAN KACA )'),
			(1445, '2', '03', '03', '03', '042', 'OPTOCAL TEKNIS GANGE ( PENGUKUR TEBAL DINDING )'),
			(1446, '2', '03', '03', '03', '043', 'LIFTER CAPASITAS 500 KG'),
			(1447, '2', '03', '03', '03', '044', 'TAXIMETER TESTER'),
			(1448, '2', '03', '03', '03', '045', 'SPEDOMETER TESTER'),
			(1449, '2', '03', '03', '03', '046', 'STANDARD GUAGE BLOCKS'),
			(1450, '2', '03', '03', '03', '047', 'FINEST DIRECT READING INTERN MICROMETER OF VARIOS RANGE UP TO 10'),
			(1451, '2', '03', '03', '03', '048', 'CONSTANT TEMPERATURE COMBINED BRIDGE THERMOSTAT'),
			(1452, '2', '03', '03', '03', '049', 'TRANSPARAN PLASTIC RACK INSERT FOR 20 TEST TEST TUBES 75 X 17'),
			(1453, '2', '03', '03', '03', '050', 'WATER BATH PLEXIGLASS CAPASITY 71'),
			(1454, '2', '03', '03', '03', '051', 'TEST TUBE RACK STAINLESSTEL WITH 10 HOLES 18 MM DIA'),
			(1455, '2', '03', '03', '03', '052', 'CALORIMETER THERMOMETER ACETO BESTMEN CERTIFICATE'),
			(1456, '2', '03', '03', '03', '053', 'SIT OF GAUGE PRETITION LANDS BERGER THERMOMETER'),
			(1457, '2', '03', '03', '03', '054', 'SET OF 14 HIGHT PRECISION AMERAL THERMOMETER'),
			(1458, '2', '03', '03', '03', '055', 'ADDITION TUNER STOP WATCH'),
			(1459, '2', '03', '03', '03', '056', '\"UNIVERSAL CLAMP'),
			(1460, '2', '03', '03', '03', '057', '\"UNIVERSAL CLAMP'),
			(1461, '2', '03', '03', '03', '058', 'VENIER CALIVER'),
			(1462, '2', '03', '03', '03', '059', 'PROPILE PROYEKTOR TOYO SERIE'),
			(1463, '2', '03', '03', '03', '060', 'TOOL MAKER MICROSCOPE MAGNIFICATION 30 X'),
			(1464, '2', '03', '03', '03', '061', 'MICROSCOPE MULTIVIEW'),
			(1465, '2', '03', '03', '03', '999', 'ALAT UKUR/PEMBANDING LAINNYA'),
			(1466, '2', '03', '03', '04', '000', 'ALAT UKUR LAINNYA'),
			(1467, '2', '03', '03', '04', '001', 'METER X - 27 DARI PLATINA TRIDIUM'),
			(1468, '2', '03', '03', '04', '002', 'H - METER DARI BAJA NIKEL'),
			(1469, '2', '03', '03', '04', '003', 'KOMPARATOR'),
			(1470, '2', '03', '03', '04', '004', 'ALAT PENGUKUR GARIS TENGAH'),
			(1471, '2', '03', '03', '04', '005', 'BAN UKUR'),
			(1472, '2', '03', '03', '04', '006', 'DIAMETER TAPE'),
			(1473, '2', '03', '03', '04', '007', 'UKURAN TINGGI ORANG'),
			(1474, '2', '03', '03', '04', '008', 'SCHUIFMAAT ( UKURAN INGSUT )'),
			(1475, '2', '03', '03', '04', '009', 'LIFTER STANDARD ( 1 LITER )'),
			(1476, '2', '03', '03', '04', '010', 'BEJANA UKUR'),
			(1477, '2', '03', '03', '04', '011', 'ALAT UKUR KADAR AIR (ALAT UKUR LAINNYA)'),
			(1478, '2', '03', '03', '04', '012', 'ALAT UKUR PEMECAH KULIT GABAH'),
			(1479, '2', '03', '03', '04', '013', 'RAIN GAUGE'),
			(1480, '2', '03', '03', '04', '014', 'NEEDLE LIFT SENSOR'),
			(1481, '2', '03', '03', '04', '999', 'ALAT UKUR LAINNYA'),
			(1482, '2', '03', '03', '05', '000', 'ALAT TIMBANGAN/BIARA'),
			(1483, '2', '03', '03', '05', '001', 'TIMBANGAN JEMBATAN CAPASITAS 10 TON'),
			(1484, '2', '03', '03', '05', '002', 'TIMBANGAN MEJA CAPASITAS 10 KG'),
			(1485, '2', '03', '03', '05', '003', 'TIMBANGAN MEJA CAPASITAS 5 KG'),
			(1486, '2', '03', '03', '05', '004', 'TIMBANGAN BBI CAPASITAS 100 KG'),
			(1487, '2', '03', '03', '05', '005', 'TIMBANGAN BBI CAPASITAS 25 KG'),
			(1488, '2', '03', '03', '05', '006', 'TIMBANGAN BBI CAPASITAS 15 KG ( TIMBANGAN BAYI )'),
			(1489, '2', '03', '03', '05', '007', 'TIMBANGAN BBI CAPASITAS 10 KG'),
			(1490, '2', '03', '03', '05', '008', 'TIMBANGAN CEPAT CAPASITAS 10 KG'),
			(1491, '2', '03', '03', '05', '009', 'TIMBANGAN CEPAT CAPASITAS 25 KG'),
			(1492, '2', '03', '03', '05', '010', 'TIMBANGAN CEPAT CAPASITAS 200 KG'),
			(1493, '2', '03', '03', '05', '011', 'TIMBANGAN PEGAS CAPASITAS 10 KG'),
			(1494, '2', '03', '03', '05', '012', 'TIMBANGAN PEGAS CAPASITAS 50 KG (ALAT TIMBANGAN/BIARA)'),
			(1495, '2', '03', '03', '05', '014', 'TIMBANGAN SURAT CAPASITAS 100 KG'),
			(1496, '2', '03', '03', '05', '015', 'TIMBANGAN KWADRAN CAPASITAS 100 KG'),
			(1497, '2', '03', '03', '05', '016', 'TIMBANGAN SENTISIMAL DACIN KUNINGAN'),
			(1498, '2', '03', '03', '05', '017', 'TIMBANGAN GULA GAVEKA'),
			(1499, '2', '03', '03', '05', '018', 'TIMBANGAN GANTUNG CAPASITAS 50 GRAM'),
			(1500, '2', '03', '03', '05', '019', 'NERACA HALUS + LEMARI CAPASITAS 500 GRAM'),
			(1501, '2', '03', '03', '05', '020', 'NERACA PARAMA E'),
			(1502, '2', '03', '03', '05', '021', 'NERACA PARAMA D CAPASITAS 5 GRAM'),
			(1503, '2', '03', '03', '05', '022', 'NERACA PERCISI ELEKTRONIK CAPASITAS 1 KG.'),
			(1504, '2', '03', '03', '05', '023', 'NERACA PERCISI ( SINGLE PAN ) CAPASITAS 20 KG.'),
			(1505, '2', '03', '03', '05', '024', 'NERACA PERCISI ( ELEKTRONIK VACUM ME )'),
			(1506, '2', '03', '03', '05', '025', 'NERACA PERCISI 30 KG ( MICRO BALANCE )'),
			(1507, '2', '03', '03', '05', '026', 'NERACA PERCISI CAPASITAS 50 GRAM'),
			(1508, '2', '03', '03', '05', '027', 'NERACA PERCISI CAPASITAS 1 KG.'),
			(1509, '2', '03', '03', '05', '028', 'NERACA TERA E'),
			(1510, '2', '03', '03', '05', '029', 'NERACA TERA A CAPASITAS 75 KG.'),
			(1511, '2', '03', '03', '05', '030', 'NERACA TERA B CAPASITAS 10 KG.'),
			(1512, '2', '03', '03', '05', '031', 'NERACA TORSION BALANCE CAPASITAS 500 GRAM'),
			(1513, '2', '03', '03', '05', '032', 'NERACA ANALISA CAPASITAS 1000 GRAM'),
			(1514, '2', '03', '03', '05', '033', 'NERACA ANALISA CAPASITAS 20 KG'),
			(1515, '2', '03', '03', '05', '034', 'NERACA CAPASITAS 1 KG.'),
			(1516, '2', '03', '03', '05', '035', 'NERACA CAPASITAS 20 KG.'),
			(1517, '2', '03', '03', '05', '036', 'MOISTER METER'),
			(1518, '2', '03', '03', '05', '037', 'NERACA DENGAN DIGITAL DISPLAY'),
			(1519, '2', '03', '03', '05', '999', 'ALAT TIMBANGAN/BIARA LAINNYA'),
			(1520, '2', '03', '03', '06', '000', 'ANAK TIMBANGAN / BIARA'),
			(1521, '2', '03', '03', '06', '001', 'KILOGRAM TEMBAGA NASIONAL PLATINA'),
			(1522, '2', '03', '03', '06', '002', 'KILOGRAM TEMBAGA BENTUK TONG BERSADUR MAS MURNI 1 KG.'),
			(1523, '2', '03', '03', '06', '003', 'KILOGRAM SEPUH MAS 1 KG. PAKAI TOMBOL'),
			(1524, '2', '03', '03', '06', '004', 'KILOGRAM BAJA BERBENTUK TONG BERSADUR CROOM'),
			(1525, '2', '03', '03', '06', '005', 'KILOGRAM DARI BAJA BERBENTUK SLINDER'),
			(1526, '2', '03', '03', '06', '006', 'KILOGRAM KERJA STANDAR TK.II'),
			(1527, '2', '03', '03', '06', '007', 'KILOGRAM STANDAR'),
			(1528, '2', '03', '03', '06', '008', 'ANAK TIMBANGAN TEMBAGA KANTOR TK.III'),
			(1529, '2', '03', '03', '06', '009', 'ANAK TIMBANGAN MILIGRAM'),
			(1530, '2', '03', '03', '06', '010', 'ANAK TIMBANGAN MILIGRAM PLATINA'),
			(1531, '2', '03', '03', '06', '011', 'ANAK TIMBANGAN MILIGRAM ALUMINIUM'),
			(1532, '2', '03', '03', '06', '012', 'ANAK TIMBANGAN GRAM STANDAR 1 GRAM'),
			(1533, '2', '03', '03', '06', '013', 'ANAK TIMBANGAN HALUS DARI 1.000 - 1 GRAM'),
			(1534, '2', '03', '03', '06', '014', 'ANAK TIMBANGAN BIASA DARI 1.000 - 1 GRAM'),
			(1535, '2', '03', '03', '06', '015', 'ANAK TIMBANGAN BIDUR'),
			(1536, '2', '03', '03', '06', '016', 'ANAK TIMBANGAN DARI BESI'),
			(1537, '2', '03', '03', '06', '017', 'ANAK TIMBANGAN KEPING ( MULUT KECIL )'),
			(1538, '2', '03', '03', '06', '018', 'ANAK TIMBANGAN KEPING ( MULUT BESAR )'),
			(1539, '2', '03', '03', '06', '999', 'ANAK TIMBANGAN / BIARA LAINNYA'),
			(1540, '2', '03', '03', '07', '000', 'TAKARAN KERING'),
			(1541, '2', '03', '03', '07', '001', 'TAKARAN KERING DARI 100 - 50 - 20 LITER'),
			(1542, '2', '03', '03', '07', '002', '\"TAKARAN KERING DARI 10 S/D 0'),
			(1543, '2', '03', '03', '07', '999', 'TAKARAN KERING LAINNYA'),
			(1544, '2', '03', '03', '08', '000', 'TAKARAN BAHAN BANGUNAN'),
			(1545, '2', '03', '03', '08', '001', 'TAKARAN BAHAN BANGUNAN 2 HL BERBENTUK TONG'),
			(1546, '2', '03', '03', '08', '999', 'TAKARAN BAHAN BANGUNAN LAINNYA'),
			(1547, '2', '03', '03', '09', '000', 'TAKARAN LAINNYA'),
			(1548, '2', '03', '03', '09', '001', 'TAKARAN LATEX/GETAH SUSU'),
			(1549, '2', '03', '03', '09', '002', '\"TAKARAN BUAH KOPI DARI 0'),
			(1550, '2', '03', '03', '09', '003', 'TAKARAN KAPUK DARI KAYU 2 DAN 1 HL'),
			(1551, '2', '03', '03', '09', '004', '\"TAKARAN MINYAK DARI BESI 0'),
			(1552, '2', '03', '03', '09', '005', '\"TAKARAN GANDUM 0'),
			(1553, '2', '03', '03', '09', '999', 'TAKARAN LAINNYA'),
			(1554, '2', '03', '03', '99', '999', 'ALAT UKUR LAINNYA'),
			(1555, '2', '04', '00', '00', '000', 'ALAT PERTANIAN'),
			(1556, '2', '04', '01', '00', '000', 'ALAT PENGOLAHAN'),
			(1557, '2', '04', '01', '01', '000', 'ALAT PENGOLAHAN TANAH DAN TANAMAN'),
			(1558, '2', '04', '01', '01', '001', 'BAJAK KAYU'),
			(1559, '2', '04', '01', '01', '002', 'BAJAK MUARA'),
			(1560, '2', '04', '01', '01', '003', 'PACUL'),
			(1561, '2', '04', '01', '01', '004', 'LINGGIS'),
			(1562, '2', '04', '01', '01', '005', 'GARPU PACUL'),
			(1563, '2', '04', '01', '01', '006', 'GARPU KAYU'),
			(1564, '2', '04', '01', '01', '007', 'GARPU BESI'),
			(1565, '2', '04', '01', '01', '008', 'TRACTOR FOUR WHEEL (DENGAN KELENGKAPANNYA)'),
			(1566, '2', '04', '01', '01', '009', 'TRACTOR TANGAN DENGAN PERLENGKAPANNYA'),
			(1567, '2', '04', '01', '01', '999', 'ALAT PENGOLAHAN TANAH DAN TANAMAN LAINNYA'),
			(1568, '2', '04', '01', '02', '000', 'ALAT PEMELIHARAAN TANAMAN/IKAN/TERNAK'),
			(1569, '2', '04', '01', '02', '001', 'KORED'),
			(1570, '2', '04', '01', '02', '002', 'ARIT'),
			(1571, '2', '04', '01', '02', '003', 'BABATAN'),
			(1572, '2', '04', '01', '02', '004', 'PACUL DANGIR'),
			(1573, '2', '04', '01', '02', '005', 'PENYEMPROT OTOMATIS (AUTOMATIC SPRAYER)'),
			(1574, '2', '04', '01', '02', '006', 'PENYEMPROT MESIN (POWER SPRAYER)'),
			(1575, '2', '04', '01', '02', '007', 'PENYEMPROT TANGAN (HAND SPRAYER)'),
			(1576, '2', '04', '01', '02', '008', 'ALAT PENYIANG TANAMAN'),
			(1577, '2', '04', '01', '02', '999', 'ALAT PEMELIHARAAN TANAMAN/IKAN/TERNAK LAINNYA'),
			(1578, '2', '04', '01', '03', '000', 'ALAT PANEN'),
			(1579, '2', '04', '01', '03', '001', 'ANI-ANI'),
			(1580, '2', '04', '01', '03', '002', 'ALAT PERONTOKAN (THRESSER PEDAL)'),
			(1581, '2', '04', '01', '03', '003', 'ALAT PERONTOKAN MESIN (POWER THRESSER)'),
			(1582, '2', '04', '01', '03', '004', 'ALAT PEMIPIL JAGUNG'),
			(1583, '2', '04', '01', '03', '005', 'ALAT PENGERING (DRYER)'),
			(1584, '2', '04', '01', '03', '006', 'ALAT PENGUKUR KADAR AIR (MOISTURE TESTER)'),
			(1585, '2', '04', '01', '03', '007', 'ALAT PENGGILING KOPI'),
			(1586, '2', '04', '01', '03', '008', 'ALAT PENGOLAH TEPUNG'),
			(1587, '2', '04', '01', '03', '009', 'ALAT BANTU UJI TUMBUH'),
			(1588, '2', '04', '01', '03', '010', 'ALAT PENAMPI'),
			(1589, '2', '04', '01', '03', '999', 'ALAT PANEN LAINNYA'),
			(1590, '2', '04', '01', '04', '000', 'ALAT PENYIMPAN HASIL PERCOBAAN PERTANIAN'),
			(1591, '2', '04', '01', '04', '001', 'COLD STORAGE (KAMAR PENDINGIN)'),
			(1592, '2', '04', '01', '04', '002', 'SELO (KOTAK PENYIMPANAN) DENGAN PENGATUR TEMPERATUR'),
			(1593, '2', '04', '01', '04', '003', 'RAK-RAK PENYIMPAN'),
			(1594, '2', '04', '01', '04', '004', 'LEMARI PENYIMPAN'),
			(1595, '2', '04', '01', '04', '999', 'ALAT PENYIMPAN HASIL PERCOBAAN PERTANIAN LAINNYA'),
			(1596, '2', '04', '01', '05', '000', 'ALAT LABORATORIUM PERTANIAN'),
			(1597, '2', '04', '01', '05', '001', 'ALAT PENGUKUR CURAH HUJAN'),
			(1598, '2', '04', '01', '05', '002', 'ALAT PENGUKUR CAHAYA'),
			(1599, '2', '04', '01', '05', '003', 'ALAT PENGUKUR INTENSITAS CAHAYA'),
			(1600, '2', '04', '01', '05', '004', 'ALAT PENGUKUR TEMPERATUR'),
			(1601, '2', '04', '01', '05', '005', 'ALAT PENGUKUR P.H. TANAH (SOIL TESTER)'),
			(1602, '2', '04', '01', '05', '006', 'ALAT PENGAMBIL SAMPLE TANAH'),
			(1603, '2', '04', '01', '05', '007', 'RICE'),
			(1604, '2', '04', '01', '05', '008', 'GRINDDING MILL'),
			(1605, '2', '04', '01', '05', '009', 'VOLUME TEST'),
			(1606, '2', '04', '01', '05', '010', 'WEIGHT'),
			(1607, '2', '04', '01', '05', '011', 'STRAW FACTURE'),
			(1608, '2', '04', '01', '05', '012', 'FALLING NUMBER'),
			(1609, '2', '04', '01', '05', '013', 'ELECTRODE PH METER'),
			(1610, '2', '04', '01', '05', '014', 'ALAT PENURUN KADAR AIR MADU'),
			(1611, '2', '04', '01', '05', '999', 'ALAT LABORATORIUM PERTANIAN LAINNYA (ALAT PENGOLAHAN PERTANIAN)'),
			(1612, '2', '04', '01', '06', '000', 'ALAT PROSESING'),
			(1613, '2', '04', '01', '06', '001', 'UNIT PENGADUK'),
			(1614, '2', '04', '01', '06', '002', 'ALAT PENCABUT BULU AYAM'),
			(1615, '2', '04', '01', '06', '003', 'ALAT PEMBUAT PELET/MAKANAN TERNAK'),
			(1616, '2', '04', '01', '06', '004', 'ALAT PEMBUAT MOLASE BLOK'),
			(1617, '2', '04', '01', '06', '005', 'MESIN TETAS'),
			(1618, '2', '04', '01', '06', '006', 'MESIN PERAH SUSU'),
			(1619, '2', '04', '01', '06', '007', 'MILK CAN'),
			(1620, '2', '04', '01', '06', '008', 'PENGUPAS KULIT ARI KEDELAI'),
			(1621, '2', '04', '01', '06', '009', 'PEMARUT SERAT SERBA GUNA'),
			(1622, '2', '04', '01', '06', '010', 'PENYAWUT SINGKONG'),
			(1623, '2', '04', '01', '06', '011', 'GILINGAN BERAS'),
			(1624, '2', '04', '01', '06', '012', 'SALINA INJECTOR'),
			(1625, '2', '04', '01', '06', '013', 'SCALLER MOTOR'),
			(1626, '2', '04', '01', '06', '014', 'ULV CABINET'),
			(1627, '2', '04', '01', '06', '015', 'TLC DRAYER'),
			(1628, '2', '04', '01', '06', '016', 'MESIN PENCUCI ALAT (MIELE)'),
			(1629, '2', '04', '01', '06', '017', 'HYDROLIC PIECES'),
			(1630, '2', '04', '01', '06', '018', 'REAPER'),
			(1631, '2', '04', '01', '06', '019', 'ELECTRIC DISK CUTTER'),
			(1632, '2', '04', '01', '06', '020', 'RAGUM /CATOK'),
			(1633, '2', '04', '01', '06', '021', 'DIESEL EGGANE'),
			(1634, '2', '04', '01', '06', '022', 'ALAT PROSESING DAGING'),
			(1635, '2', '04', '01', '06', '023', 'ALAT PROSESING TELUR'),
			(1636, '2', '04', '01', '06', '024', 'ICE CREAM MAKER'),
			(1637, '2', '04', '01', '06', '025', 'HAND SEPARATOR'),
			(1638, '2', '04', '01', '06', '026', 'MESIN PENEPUNG BERAS'),
			(1639, '2', '04', '01', '06', '027', 'ALAT PENGGILING JAGUNG'),
			(1640, '2', '04', '01', '06', '028', 'MESIN PENGAYAK TEPUNG'),
			(1641, '2', '04', '01', '06', '029', 'PENGOLAHAN PRODUK KERING'),
			(1642, '2', '04', '01', '06', '030', 'PENYAWUT BESAR DAN KECIL'),
			(1643, '2', '04', '01', '06', '031', 'PROCESSING MULTIGUNA'),
			(1644, '2', '04', '01', '06', '032', 'PUMP FOR HPLC AND ACCESSORIES'),
			(1645, '2', '04', '01', '06', '033', 'SAUSAGE FEELER MACHINE'),
			(1646, '2', '04', '01', '06', '034', 'TWIN PAPER ROLLER BEARING'),
			(1647, '2', '04', '01', '06', '035', 'SKINNING CRADLE'),
			(1648, '2', '04', '01', '06', '036', 'HEAD RESTRAINER'),
			(1649, '2', '04', '01', '06', '037', 'STUNING DEVICE'),
			(1650, '2', '04', '01', '06', '038', 'PENYODOK KOTORAN'),
			(1651, '2', '04', '01', '06', '039', 'PENGARAH KEPALA'),
			(1652, '2', '04', '01', '06', '040', 'OFFAL WASH'),
			(1653, '2', '04', '01', '06', '041', 'BEEF SPLITTER'),
			(1654, '2', '04', '01', '06', '999', 'ALAT PROSESING LAINNYA'),
			(1655, '2', '04', '01', '07', '000', 'ALAT PASCA PANEN'),
			(1656, '2', '04', '01', '07', '001', 'ALAT PENGASAPAN'),
			(1657, '2', '04', '01', '07', '002', 'ALAT PEMBEKUAN'),
			(1658, '2', '04', '01', '07', '003', 'ALAT PENGGILING PADI'),
			(1659, '2', '04', '01', '07', '004', 'ALAT PENCACAH HIJAUAN'),
			(1660, '2', '04', '01', '07', '005', 'ALAT PEMECAH TAPIOKA'),
			(1661, '2', '04', '01', '07', '999', 'ALAT PASCA PANEN LAINNYA'),
			(1662, '2', '04', '01', '08', '000', 'ALAT PRODUKSI PERIKANAN'),
			(1663, '2', '04', '01', '08', '001', 'PUKAT'),
			(1664, '2', '04', '01', '08', '002', 'DOUBLE RIG SHRIMP TRAWL/PUKAT UDANG GANDA'),
			(1665, '2', '04', '01', '08', '003', 'PAYANG ( TERMASUK LAMPARA )'),
			(1666, '2', '04', '01', '08', '004', 'DANISH SEINE ( DOGOL )'),
			(1667, '2', '04', '01', '08', '005', 'BEACH SEINE ( PUKAT PANTAI )'),
			(1668, '2', '04', '01', '08', '006', 'DRIFT GILL NET ( JARING INSANG HANYUT )'),
			(1669, '2', '04', '01', '08', '007', 'ENCIRCLING GILL NET ( JARING INSANG LINGKAR )'),
			(1670, '2', '04', '01', '08', '008', 'SHRIMP GILL NET ( JARING KLITIK )'),
			(1671, '2', '04', '01', '08', '009', 'SET GILL NET ( JARING INSANG TETAP )'),
			(1672, '2', '04', '01', '08', '010', 'BOAT RAFT LIFT NET ( BAGAN PERAHU/RAKIT )'),
			(1673, '2', '04', '01', '08', '011', 'BAGAN TANCAP BERIKUT KELONG'),
			(1674, '2', '04', '01', '08', '012', 'SCOOP NET ( SEROK )'),
			(1675, '2', '04', '01', '08', '013', 'JARING ANGKAT LAINNYA'),
			(1676, '2', '04', '01', '08', '014', 'GUIDING BARRIER ( SEROK )'),
			(1677, '2', '04', '01', '08', '015', 'STOW NET ( JERMAL TERMASUK TOGO )'),
			(1678, '2', '04', '01', '08', '016', 'PORTABLE TRAPS ( BUBU )'),
			(1679, '2', '04', '01', '08', '017', 'PERANGKAP LAINNYA'),
			(1680, '2', '04', '01', '08', '018', 'TUNA LONG LINE ( RAWAI TUNA )'),
			(1681, '2', '04', '01', '08', '019', 'SET LONG LINE ( RAWAI TETAP )'),
			(1682, '2', '04', '01', '08', '020', 'SKIPJACK POLE AND LINES ( HUHATE )'),
			(1683, '2', '04', '01', '08', '021', 'TROOL LINE ( PANCING TONDA )'),
			(1684, '2', '04', '01', '08', '022', 'PANCING LAINNYA'),
			(1685, '2', '04', '01', '08', '023', 'MUROAMI INC. MALLALUGIS'),
			(1686, '2', '04', '01', '08', '024', 'JALA'),
			(1687, '2', '04', '01', '08', '025', 'GARPU'),
			(1688, '2', '04', '01', '08', '026', 'TOMBAK'),
			(1689, '2', '04', '01', '08', '027', 'SEA WATER RESERVOIR'),
			(1690, '2', '04', '01', '08', '028', 'BAK PEMELIHARAAN SEMENTARA'),
			(1691, '2', '04', '01', '08', '029', 'BAK PENGENDAPAN'),
			(1692, '2', '04', '01', '08', '030', 'KERAMBA ( JARING APUNG )'),
			(1693, '2', '04', '01', '08', '031', 'JARING LINGKAR'),
			(1694, '2', '04', '01', '08', '032', 'PUKAT TARIK BERKAPAL'),
			(1695, '2', '04', '01', '08', '033', 'PUKAT HELA'),
			(1696, '2', '04', '01', '08', '034', 'PUKAT DORONG'),
			(1697, '2', '04', '01', '08', '035', 'PENGGARUK'),
			(1698, '2', '04', '01', '08', '036', 'JARING ANGKAT MENETAP'),
			(1699, '2', '04', '01', '08', '037', 'JARING ANGKAT TIDAK MENETAP'),
			(1700, '2', '04', '01', '08', '038', 'ALAT YANG DIJATUHKAN'),
			(1701, '2', '04', '01', '08', '039', 'ALAT PENJEPIT DAN MELUKAI'),
			(1702, '2', '04', '01', '08', '999', 'ALAT PRODUKSI PERIKANAN LAINNYA'),
			(1703, '2', '04', '01', '99', '000', 'ALAT PENGOLAHAN LAINNYA'),
			(1704, '2', '04', '01', '99', '999', 'ALAT PENGOLAHAN LAINNYA'),
			(1705, '2', '05', '00', '00', '000', 'ALAT KANTOR & RUMAH TANGGA'),
			(1706, '2', '05', '01', '00', '000', 'ALAT KANTOR'),
			(1707, '2', '05', '01', '01', '000', 'MESIN KETIK'),
			(1708, '2', '05', '01', '01', '001', 'MESIN KETIK MANUAL PORTABLE (11-13 INCI)'),
			(1709, '2', '05', '01', '01', '002', 'MESIN KETIK MANUAL STANDARD (14-16 INCI)'),
			(1710, '2', '05', '01', '01', '003', 'MESIN KETIK MANUAL LANGEWAGON (18-27 INCI)'),
			(1711, '2', '05', '01', '01', '004', 'MESIN KETIK LISTRIK'),
			(1712, '2', '05', '01', '01', '005', 'MESIN KETIK LISTRIK POTABLE (11-13 INCI)'),
			(1713, '2', '05', '01', '01', '006', 'MESIN KETIK LISTRIK STANDARD (14-16 INCI)'),
			(1714, '2', '05', '01', '01', '007', 'MESIN KETIK LISTRIK LANGEWAGON (18-27 INCI)'),
			(1715, '2', '05', '01', '01', '008', 'MESIN KETIK ELEKTRONIK/SELEKTRIK'),
			(1716, '2', '05', '01', '01', '009', 'MESIN KETIK BRAILLE'),
			(1717, '2', '05', '01', '01', '010', 'MESIN PHROMOSONS'),
			(1718, '2', '05', '01', '01', '011', 'MESIN CETAK STEREO PIPER (BRAILLE)'),
			(1719, '2', '05', '01', '01', '999', 'MESIN KETIK LAINNYA'),
			(1720, '2', '05', '01', '02', '000', 'MESIN HITUNG/MESIN JUMLAH'),
			(1721, '2', '05', '01', '02', '001', 'MESIN HITUNG MANUAL'),
			(1722, '2', '05', '01', '02', '002', 'MESIN HITUNG LISTRIK'),
			(1723, '2', '05', '01', '02', '003', 'MESIN HITUNG ELEKTRONIK/CALCULATOR'),
			(1724, '2', '05', '01', '02', '004', 'MESIN KAS REGISTER'),
			(1725, '2', '05', '01', '02', '005', 'ABAKUS (ALAT HITUNG)'),
			(1726, '2', '05', '01', '02', '006', 'BLOKYCS (MESIN HITUNG BRAILLE)'),
			(1727, '2', '05', '01', '02', '007', 'MESIN PENGHITUNG UANG'),
			(1728, '2', '05', '01', '02', '008', 'MESIN PEMBUKUAN'),
			(1729, '2', '05', '01', '02', '009', 'MESIN PENGHITUNG KERTAS/PITA CUKAI'),
			(1730, '2', '05', '01', '02', '999', 'MESIN HITUNG/MESIN JUMLAH LAINNYA'),
			(1731, '2', '05', '01', '03', '000', 'ALAT REPRODUKSI (PENGGANDAAN)'),
			(1732, '2', '05', '01', '03', '001', 'MESIN STENSIL MANUAL FOLIO'),
			(1733, '2', '05', '01', '03', '002', 'MESIN STENSIL MANUAL DOUBLE FOLIO'),
			(1734, '2', '05', '01', '03', '003', 'MESIN STENSIL LISTRIK FOLIO'),
			(1735, '2', '05', '01', '03', '004', 'MESIN STENSIL LISTRIK DOUBLE FOLIO'),
			(1736, '2', '05', '01', '03', '005', 'MESIN STENSIL SPIRITUS MANUAL'),
			(1737, '2', '05', '01', '03', '006', 'MESIN STENSIL SPIRITUS LISTRIK'),
			(1738, '2', '05', '01', '03', '007', 'MESIN FOTOCOPY FOLIO'),
			(1739, '2', '05', '01', '03', '008', 'MESIN FOTOCOPY DOUBLE FOLIO'),
			(1740, '2', '05', '01', '03', '009', 'MESIN FOTOCOPY ELECTRONIC'),
			(1741, '2', '05', '01', '03', '010', 'MESIN THERMOFORN'),
			(1742, '2', '05', '01', '03', '011', 'MESIN FOTOCOPY LAINNYA'),
			(1743, '2', '05', '01', '03', '012', 'RISOGRAF'),
			(1744, '2', '05', '01', '03', '999', 'ALAT REPRODUKSI (PENGGANDAAN) LAINNYA'),
			(1745, '2', '05', '01', '04', '000', 'ALAT PENYIMPAN PERLENGKAPAN KANTOR'),
			(1746, '2', '05', '01', '04', '001', 'LEMARI BESI/METAL'),
			(1747, '2', '05', '01', '04', '002', 'LEMARI KAYU'),
			(1748, '2', '05', '01', '04', '003', 'RAK BESI'),
			(1749, '2', '05', '01', '04', '004', 'RAK KAYU'),
			(1750, '2', '05', '01', '04', '005', 'FILING CABINET BESI'),
			(1751, '2', '05', '01', '04', '006', 'FILING CABINET KAYU'),
			(1752, '2', '05', '01', '04', '007', 'BRANDKAS'),
			(1753, '2', '05', '01', '04', '008', 'PETI UANG/CASH BOX/COIN BOX'),
			(1754, '2', '05', '01', '04', '009', 'KARDEX BESI'),
			(1755, '2', '05', '01', '04', '010', 'KARDEX KAYU'),
			(1756, '2', '05', '01', '04', '011', 'ROTARY FILLING'),
			(1757, '2', '05', '01', '04', '012', 'COMPACT ROLLING'),
			(1758, '2', '05', '01', '04', '013', 'BUFFET'),
			(1759, '2', '05', '01', '04', '014', 'MOBILE FILE'),
			(1760, '2', '05', '01', '04', '015', 'LOCKER'),
			(1761, '2', '05', '01', '04', '016', 'ROLL OPEK'),
			(1762, '2', '05', '01', '04', '017', 'TEMPAT MENYIMPAN GAMBAR'),
			(1763, '2', '05', '01', '04', '018', 'KONTAINER'),
			(1764, '2', '05', '01', '04', '019', 'COIN BOX'),
			(1765, '2', '05', '01', '04', '020', 'LEMARI DISPLAY'),
			(1766, '2', '05', '01', '04', '021', 'WATER PROOF BOX'),
			(1767, '2', '05', '01', '04', '022', 'FOLDING CONTAINER BOX'),
			(1768, '2', '05', '01', '04', '023', 'BOX TRUCK'),
			(1769, '2', '05', '01', '04', '024', 'LACI BOX'),
			(1770, '2', '05', '01', '04', '025', 'LEMARI KATALOG'),
			(1771, '2', '05', '01', '04', '999', 'ALAT PENYIMPAN PERLENGKAPAN KANTOR LAINNYA'),
			(1772, '2', '05', '01', '05', '000', 'ALAT KANTOR LAINNYA'),
			(1773, '2', '05', '01', '05', '001', 'TABUNG PEMADAM API'),
			(1774, '2', '05', '01', '05', '002', 'HYDRANT'),
			(1775, '2', '05', '01', '05', '003', 'SPRINKLER'),
			(1776, '2', '05', '01', '05', '004', 'FIRE ALARM'),
			(1777, '2', '05', '01', '05', '005', 'RAMBU-RAMBU'),
			(1778, '2', '05', '01', '05', '006', 'NARKOTIK TEST'),
			(1779, '2', '05', '01', '05', '007', 'CCTV - CAMERA CONTROL TELEVISION SYSTEM'),
			(1780, '2', '05', '01', '05', '008', 'PAPAN VISUAL/PAPAN NAMA'),
			(1781, '2', '05', '01', '05', '009', 'MOVITEX BOARD'),
			(1782, '2', '05', '01', '05', '010', 'WHITE BOARD'),
			(1783, '2', '05', '01', '05', '011', 'ALAT DETEKTOR UANG PALSU'),
			(1784, '2', '05', '01', '05', '012', 'ALAT DETEKTOR BARANG TERLARANG/X RAY'),
			(1785, '2', '05', '01', '05', '013', 'COPY BOARD/ELEKTRIC WHITE BOARD'),
			(1786, '2', '05', '01', '05', '014', 'PETA'),
			(1787, '2', '05', '01', '05', '015', 'ALAT PENGHANCUR KERTAS'),
			(1788, '2', '05', '01', '05', '016', 'GLOBE'),
			(1789, '2', '05', '01', '05', '017', 'MESIN ABSENSI'),
			(1790, '2', '05', '01', '05', '018', 'DRY SEAL'),
			(1791, '2', '05', '01', '05', '019', 'FERGULATOR'),
			(1792, '2', '05', '01', '05', '020', 'CREAM POLISHER'),
			(1793, '2', '05', '01', '05', '021', 'MESIN PERANGKO'),
			(1794, '2', '05', '01', '05', '022', 'CHECK WRITER'),
			(1795, '2', '05', '01', '05', '023', 'NUMERATOR'),
			(1796, '2', '05', '01', '05', '024', 'ALAT PEMOTONG KERTAS'),
			(1797, '2', '05', '01', '05', '025', 'HEADMACHINE BESAR'),
			(1798, '2', '05', '01', '05', '026', 'PERFORATOR BESAR'),
			(1799, '2', '05', '01', '05', '027', 'ALAT PENCETAK LABEL'),
			(1800, '2', '05', '01', '05', '028', 'OVERHEAD PROJECTOR'),
			(1801, '2', '05', '01', '05', '029', 'HAND METAL DETECTOR'),
			(1802, '2', '05', '01', '05', '030', 'WALKMAN DETECTOR'),
			(1803, '2', '05', '01', '05', '031', 'PANEL PAMERAN'),
			(1804, '2', '05', '01', '05', '032', 'ALAT PENGAMAN / SINYAL'),
			(1805, '2', '05', '01', '05', '033', 'BOARD MODULUX'),
			(1806, '2', '05', '01', '05', '034', 'PORTO SAFE TRAVEL COSE'),
			(1807, '2', '05', '01', '05', '035', 'DISK PRIME'),
			(1808, '2', '05', '01', '05', '036', 'MEGASHOW'),
			(1809, '2', '05', '01', '05', '037', 'WHITE BOARD ELECTRONIC'),
			(1810, '2', '05', '01', '05', '038', 'LASER POINTER'),
			(1811, '2', '05', '01', '05', '039', 'DISPLAY'),
			(1812, '2', '05', '01', '05', '040', 'EXHAUSTER FORM'),
			(1813, '2', '05', '01', '05', '041', 'RUBU MUJAYYAB'),
			(1814, '2', '05', '01', '05', '042', 'ELECTRIC DUMPER'),
			(1815, '2', '05', '01', '05', '043', 'MESIN TERAAN'),
			(1816, '2', '05', '01', '05', '044', 'MESIN LAMINATING'),
			(1817, '2', '05', '01', '05', '045', 'PENANGKAL PETIR'),
			(1818, '2', '05', '01', '05', '046', 'STEMPEL TIMBUL/BULAT'),
			(1819, '2', '05', '01', '05', '047', 'LAMPU-LAMPU KRISTAL'),
			(1820, '2', '05', '01', '05', '048', 'LCD PROJECTOR/INFOCUS'),
			(1821, '2', '05', '01', '05', '049', 'FLIP CHART'),
			(1822, '2', '05', '01', '05', '050', 'BINDING MACHINE'),
			(1823, '2', '05', '01', '05', '051', 'SOFTBOARD'),
			(1824, '2', '05', '01', '05', '052', 'ALAT PEREKAM SUARA (VOICE PEN)'),
			(1825, '2', '05', '01', '05', '053', 'ACCES CONTROL SYSTEM'),
			(1826, '2', '05', '01', '05', '054', 'INTRUCTION DETECTOR'),
			(1827, '2', '05', '01', '05', '055', 'MONITOR PANEL WITH MIMIC BOARD'),
			(1828, '2', '05', '01', '05', '056', '\"PANIC BUTTON SYSTEM'),
			(1829, '2', '05', '01', '05', '057', 'PINTU ELEKTRIK (YANG MEMAKAI AKSES)'),
			(1830, '2', '05', '01', '05', '058', 'FOCUSING SCREEN/LAYAR LCD PROJECTOR'),
			(1831, '2', '05', '01', '05', '059', 'ALAT DETEKTOR BARANG TERLARANG'),
			(1832, '2', '05', '01', '05', '060', 'PROYECTOR SPIDER BRACKET'),
			(1833, '2', '05', '01', '05', '061', 'PAPAN GAMBAR'),
			(1834, '2', '05', '01', '05', '062', 'BEL'),
			(1835, '2', '05', '01', '05', '063', 'ELECTRIC PRESSING MACHINE'),
			(1836, '2', '05', '01', '05', '064', 'ENCAPSULATOR (JARASONIC WELDER)'),
			(1837, '2', '05', '01', '05', '065', 'DEACIDIFICATOR UNIT (NON AQUAS)'),
			(1838, '2', '05', '01', '05', '066', 'FULL AUTOMATIC LEAF CASTER'),
			(1839, '2', '05', '01', '05', '067', 'CONSERVATION TOOLS'),
			(1840, '2', '05', '01', '05', '068', 'BOARD STAN'),
			(1841, '2', '05', '01', '05', '069', 'VACUM FREEZE DRY CHAMBER'),
			(1842, '2', '05', '01', '05', '070', 'KOTAK SURAT'),
			(1843, '2', '05', '01', '05', '071', 'GEMBOK'),
			(1844, '2', '05', '01', '05', '072', 'COMPACT HAND PROJECTOR'),
			(1845, '2', '05', '01', '05', '073', 'ALAT SIDIK JARI'),
			(1846, '2', '05', '01', '05', '074', 'ALAT PENGHANCUR JARUM'),
			(1847, '2', '05', '01', '05', '075', 'WALKTHROUGH/ PORTAL METAL DETECTOR'),
			(1848, '2', '05', '01', '05', '076', 'HANDHELD TRACE DETECTOR'),
			(1849, '2', '05', '01', '05', '077', 'ALAT DETEKSI PITA CUKAI PALSU/ VIDEO SPECTRAL COMPARATOR'),
			(1850, '2', '05', '01', '05', '078', 'MESIN PACKING/ STARPPING MACHINE'),
			(1851, '2', '05', '01', '05', '079', 'TELEVISION CONTROL OPERASIONAL LIFT'),
			(1852, '2', '05', '01', '05', '080', 'MESIN ANTRIAN'),
			(1853, '2', '05', '01', '05', '081', 'PAPAN PENGUMUMAN'),
			(1854, '2', '05', '01', '05', '082', 'MESIN FOGGING'),
			(1855, '2', '05', '01', '05', '083', 'TERALIS'),
			(1856, '2', '05', '01', '05', '999', 'PERKAKAS KANTOR LAINNYA'),
			(1857, '2', '05', '01', '99', '000', 'ALAT KANTOR LAINNYA'),
			(1858, '2', '05', '01', '99', '999', 'ALAT KANTOR LAINNYA'),
			(1859, '2', '05', '02', '00', '000', 'ALAT RUMAH TANGGA'),
			(1860, '2', '05', '02', '01', '000', 'MEUBELAIR'),
			(1861, '2', '05', '02', '01', '001', 'MEJA KERJA BESI/METAL'),
			(1862, '2', '05', '02', '01', '002', 'MEJA KERJA KAYU'),
			(1863, '2', '05', '02', '01', '003', 'KURSI BESI/METAL'),
			(1864, '2', '05', '02', '01', '004', 'KURSI KAYU'),
			(1865, '2', '05', '02', '01', '005', 'SICE'),
			(1866, '2', '05', '02', '01', '006', 'BANGKU PANJANG BESI/METAL'),
			(1867, '2', '05', '02', '01', '007', 'BANGKU PANJANG KAYU'),
			(1868, '2', '05', '02', '01', '008', 'MEJA RAPAT'),
			(1869, '2', '05', '02', '01', '009', 'MEJA KOMPUTER'),
			(1870, '2', '05', '02', '01', '010', 'TEMPAT TIDUR BESI'),
			(1871, '2', '05', '02', '01', '011', 'TEMPAT TIDUR KAYU'),
			(1872, '2', '05', '02', '01', '012', 'MEJA KETIK'),
			(1873, '2', '05', '02', '01', '013', 'MEJA TELEPON'),
			(1874, '2', '05', '02', '01', '014', 'MEJA RESEPSIONIS'),
			(1875, '2', '05', '02', '01', '015', 'MEJA MARMER'),
			(1876, '2', '05', '02', '01', '016', 'KASUR/SPRING BED'),
			(1877, '2', '05', '02', '01', '017', 'SKETSEL'),
			(1878, '2', '05', '02', '01', '018', 'MEJA MAKAN BESI'),
			(1879, '2', '05', '02', '01', '019', 'MEJA MAKAN KAYU'),
			(1880, '2', '05', '02', '01', '020', 'KURSI FIBER GLAS/PLASTIK'),
			(1881, '2', '05', '02', '01', '021', 'POT BUNGA'),
			(1882, '2', '05', '02', '01', '022', 'PARTISI'),
			(1883, '2', '05', '02', '01', '023', 'PUBLIK ASTARI (PEMBATAS ANTRIAN)'),
			(1884, '2', '05', '02', '01', '024', 'RAK SEPATU ( ALMUNIUM )'),
			(1885, '2', '05', '02', '01', '025', 'GANTUNGAN JAS'),
			(1886, '2', '05', '02', '01', '026', 'NAKAS'),
			(1887, '2', '05', '02', '01', '027', 'CUBIKAL'),
			(1888, '2', '05', '02', '01', '028', 'WORKSTATION'),
			(1889, '2', '05', '02', '01', '999', 'MEUBELAIR LAINNYA'),
			(1890, '2', '05', '02', '02', '000', 'ALAT PENGUKUR WAKTU'),
			(1891, '2', '05', '02', '02', '001', 'JAM MEKANIS'),
			(1892, '2', '05', '02', '02', '002', 'JAM LISTRIK'),
			(1893, '2', '05', '02', '02', '003', 'JAM ELEKTRONIK'),
			(1894, '2', '05', '02', '02', '004', 'CONTROL CLOCK'),
			(1895, '2', '05', '02', '02', '999', 'ALAT PENGUKUR WAKTU LAINNYA'),
			(1896, '2', '05', '02', '03', '000', 'ALAT PEMBERSIH'),
			(1897, '2', '05', '02', '03', '001', 'MESIN PENGHISAP DEBU/VACUUM CLEANER'),
			(1898, '2', '05', '02', '03', '002', 'MESIN PEL/POLES'),
			(1899, '2', '05', '02', '03', '003', 'MESIN PEMOTONG RUMPUT'),
			(1900, '2', '05', '02', '03', '004', 'MESIN CUCI'),
			(1901, '2', '05', '02', '03', '005', 'AIR CLEANER'),
			(1902, '2', '05', '02', '03', '006', 'ALAT PEMBERSIH SALJU'),
			(1903, '2', '05', '02', '03', '999', 'ALAT PEMBERSIH LAINNYA'),
			(1904, '2', '05', '02', '04', '000', 'ALAT PENDINGIN'),
			(1905, '2', '05', '02', '04', '001', 'LEMARI ES'),
			(1906, '2', '05', '02', '04', '002', 'A.C. SENTRAL'),
			(1907, '2', '05', '02', '04', '003', 'A.C. WINDOW'),
			(1908, '2', '05', '02', '04', '004', 'A.C. SPLIT'),
			(1909, '2', '05', '02', '04', '005', 'PORTABLE AIR CONDITIONER (ALAT PENDINGIN)'),
			(1910, '2', '05', '02', '04', '006', 'KIPAS ANGIN'),
			(1911, '2', '05', '02', '04', '007', 'EXHAUSE FAN'),
			(1912, '2', '05', '02', '04', '008', 'COLD STORAGE (ALAT PENDINGIN)'),
			(1913, '2', '05', '02', '04', '009', 'REACH IN FREZZER'),
			(1914, '2', '05', '02', '04', '010', 'REACH IN CHILLER'),
			(1915, '2', '05', '02', '04', '011', 'UP RIGHT CHILLER/FREZZER'),
			(1916, '2', '05', '02', '04', '012', 'COLD ROOM FREZZER'),
			(1917, '2', '05', '02', '04', '013', 'AIR CURTAIN'),
			(1918, '2', '05', '02', '04', '014', 'AIR HANDLING UNIT'),
			(1919, '2', '05', '02', '04', '999', 'ALAT PENDINGIN LAINNYA'),
			(1920, '2', '05', '02', '05', '000', 'ALAT DAPUR'),
			(1921, '2', '05', '02', '05', '001', 'KOMPOR LISTRIK (ALAT DAPUR)'),
			(1922, '2', '05', '02', '05', '002', 'KOMPOR GAS (ALAT DAPUR)'),
			(1923, '2', '05', '02', '05', '003', 'KOMPOR MINYAK'),
			(1924, '2', '05', '02', '05', '004', 'TEKO LISTRIK'),
			(1925, '2', '05', '02', '05', '005', 'RICE COOKER (ALAT DAPUR)'),
			(1926, '2', '05', '02', '05', '006', 'OVEN LISTRIK'),
			(1927, '2', '05', '02', '05', '007', 'RICE WARMER'),
			(1928, '2', '05', '02', '05', '008', 'KITCHEN SET'),
			(1929, '2', '05', '02', '05', '009', 'TABUNG GAS'),
			(1930, '2', '05', '02', '05', '010', 'MESIN GILING BUMBU'),
			(1931, '2', '05', '02', '05', '011', 'TRENG AIR/TANDON AIR'),
			(1932, '2', '05', '02', '05', '012', 'MESIN PARUTAN KELAPA'),
			(1933, '2', '05', '02', '05', '013', 'KOMPOR KOMPRESOR'),
			(1934, '2', '05', '02', '05', '014', 'ALAT PEMANGGANG ROTI/SATE'),
			(1935, '2', '05', '02', '05', '015', 'RAK PIRING ALUMUNIUM'),
			(1936, '2', '05', '02', '05', '016', 'ALAT PENYIMPAN BERAS'),
			(1937, '2', '05', '02', '05', '017', 'PANCI'),
			(1938, '2', '05', '02', '05', '018', 'BLENDER'),
			(1939, '2', '05', '02', '05', '019', 'MIXER'),
			(1940, '2', '05', '02', '05', '020', 'OVEN GAS'),
			(1941, '2', '05', '02', '05', '021', 'PRESTO COOKER'),
			(1942, '2', '05', '02', '05', '022', 'WONDER PAN'),
			(1943, '2', '05', '02', '05', '023', 'MESIN GILING DAGING'),
			(1944, '2', '05', '02', '05', '024', 'HEATING SET'),
			(1945, '2', '05', '02', '05', '025', 'THERMOS AIR'),
			(1946, '2', '05', '02', '05', '999', 'ALAT DAPUR LAINNYA'),
			(1947, '2', '05', '02', '06', '000', 'ALAT RUMAH TANGGA LAINNYA ( HOME USE )'),
			(1948, '2', '05', '02', '06', '001', 'RADIO'),
			(1949, '2', '05', '02', '06', '002', 'TELEVISI'),
			(1950, '2', '05', '02', '06', '003', 'VIDEO CASSETTE'),
			(1951, '2', '05', '02', '06', '004', 'TAPE RECORDER (ALAT RUMAH TANGGA LAINNYA ( HOME USE ))'),
			(1952, '2', '05', '02', '06', '005', 'AMPLIFIER'),
			(1953, '2', '05', '02', '06', '006', 'EQUALIZER'),
			(1954, '2', '05', '02', '06', '007', 'LOUDSPEAKER'),
			(1955, '2', '05', '02', '06', '008', 'SOUND SYSTEM'),
			(1956, '2', '05', '02', '06', '009', 'COMPACT DISC'),
			(1957, '2', '05', '02', '06', '010', 'LASER DISC'),
			(1958, '2', '05', '02', '06', '011', 'KARAOKE'),
			(1959, '2', '05', '02', '06', '012', 'WIRELESS'),
			(1960, '2', '05', '02', '06', '013', 'MEGAPHONE'),
			(1961, '2', '05', '02', '06', '014', 'MICROPHONE'),
			(1962, '2', '05', '02', '06', '015', 'MICROPHONE TABLE STAND'),
			(1963, '2', '05', '02', '06', '016', 'MIC CONFERENCE'),
			(1964, '2', '05', '02', '06', '017', 'UNIT POWER SUPPLY'),
			(1965, '2', '05', '02', '06', '018', 'STEP UP/DOWN (ALAT RUMAH TANGGA LAINNYA ( HOME USE ))'),
			(1966, '2', '05', '02', '06', '019', 'STABILISATOR'),
			(1967, '2', '05', '02', '06', '020', 'CAMERA VIDEO'),
			(1968, '2', '05', '02', '06', '021', 'TUSTEL'),
			(1969, '2', '05', '02', '06', '022', 'MESIN JAHIT'),
			(1970, '2', '05', '02', '06', '023', 'TIMBANGAN ORANG'),
			(1971, '2', '05', '02', '06', '024', 'TIMBANGAN BARANG'),
			(1972, '2', '05', '02', '06', '025', 'ALAT HIASAN'),
			(1973, '2', '05', '02', '06', '026', 'LAMBANG GARUDA PANCASILA'),
			(1974, '2', '05', '02', '06', '027', 'GAMBAR PRESIDEN/WAKIL PRESIDEN'),
			(1975, '2', '05', '02', '06', '028', 'LAMBANG KORPRI/DHARMA WANITA'),
			(1976, '2', '05', '02', '06', '029', 'AQUARIUM (ALAT RUMAH TANGGA LAINNYA ( HOME USE ))'),
			(1977, '2', '05', '02', '06', '030', 'TIANG BENDERA'),
			(1978, '2', '05', '02', '06', '031', 'PATAKA'),
			(1979, '2', '05', '02', '06', '032', 'SETERIKA'),
			(1980, '2', '05', '02', '06', '033', 'WATER FILTER'),
			(1981, '2', '05', '02', '06', '034', 'TANGGA ALUMINIUM'),
			(1982, '2', '05', '02', '06', '035', 'KACA HIAS'),
			(1983, '2', '05', '02', '06', '036', 'DISPENSER'),
			(1984, '2', '05', '02', '06', '037', 'MIMBAR/PODIUM'),
			(1985, '2', '05', '02', '06', '038', 'GUCCI'),
			(1986, '2', '05', '02', '06', '039', 'TANGGA HIDROLIK'),
			(1987, '2', '05', '02', '06', '040', 'PALU SIDANG'),
			(1988, '2', '05', '02', '06', '041', 'MESIN PENGERING PAKAIAN'),
			(1989, '2', '05', '02', '06', '042', 'LAMBANG INSTANSI'),
			(1990, '2', '05', '02', '06', '043', 'LONCENG/GENTA'),
			(1991, '2', '05', '02', '06', '044', 'MESIN PEMOTONG KERAMIK'),
			(1992, '2', '05', '02', '06', '045', 'COFFEE MAKER'),
			(1993, '2', '05', '02', '06', '046', 'HANDY CAM'),
			(1994, '2', '05', '02', '06', '047', 'MESIN OBRAS'),
			(1995, '2', '05', '02', '06', '048', 'MESIN POTONG KAIN'),
			(1996, '2', '05', '02', '06', '049', 'MESIN PELUBANG KANCING'),
			(1997, '2', '05', '02', '06', '050', 'MEJA POTONG'),
			(1998, '2', '05', '02', '06', '051', 'RADER'),
			(1999, '2', '05', '02', '06', '052', 'MANEQUIN (BONEKA)'),
			(2000, '2', '05', '02', '06', '053', 'PINSET (PISAU LOBANG KANCING)'),
			(2001, '2', '05', '02', '06', '054', 'MINI COMPO'),
			(2002, '2', '05', '02', '06', '055', 'HEATER (ALAT RUMAH TANGGA LAINNYA ( HOME USE ))'),
			(2003, '2', '05', '02', '06', '056', 'KARPET'),
			(2004, '2', '05', '02', '06', '057', 'VERTIKAL BLIND'),
			(2005, '2', '05', '02', '06', '058', 'GORDYIN/KRAY'),
			(2006, '2', '05', '02', '06', '059', 'KABEL ROLL'),
			(2007, '2', '05', '02', '06', '060', 'ASBAK TINGGI'),
			(2008, '2', '05', '02', '06', '061', 'KESET KAKI'),
			(2009, '2', '05', '02', '06', '062', 'SUN SCREEN'),
			(2010, '2', '05', '02', '06', '063', 'ALAT PEMANAS RUANGAN'),
			(2011, '2', '05', '02', '06', '064', 'LEMARI PLASTIK'),
			(2012, '2', '05', '02', '06', '065', 'MESIN PENGERING TANGAN'),
			(2013, '2', '05', '02', '06', '066', 'PANGGUNG'),
			(2014, '2', '05', '02', '06', '067', 'MESIN PEDDING'),
			(2015, '2', '05', '02', '06', '068', 'DVD PLAYER'),
			(2016, '2', '05', '02', '06', '069', 'LAMPU BELAJAR'),
			(2017, '2', '05', '02', '06', '070', 'TANGGA'),
			(2018, '2', '05', '02', '06', '071', 'KABEL'),
			(2019, '2', '05', '02', '06', '072', 'LAMPU'),
			(2020, '2', '05', '02', '06', '073', 'JEMURAN'),
			(2021, '2', '05', '02', '06', '074', 'PATUNG PERAGA PAKAIAN'),
			(2022, '2', '05', '02', '06', '075', 'GENDOLA'),
			(2023, '2', '05', '02', '06', '076', 'GUNTING RUMPUT NON MESIN'),
			(2024, '2', '05', '02', '06', '077', 'BENDERA NEGARA'),
			(2025, '2', '05', '02', '06', '078', 'BINGKAI FOTO'),
			(2026, '2', '05', '02', '06', '079', 'ALAT PANGKAS RAMBUT LISTRIK'),
			(2027, '2', '05', '02', '06', '080', 'BRACKET STANDING PERALATAN'),
			(2028, '2', '05', '02', '06', '081', 'TANGKI AIR'),
			(2029, '2', '05', '02', '06', '082', 'HOME THEATER'),
			(2030, '2', '05', '02', '06', '999', 'ALAT RUMAH TANGGA LAINNYA ( HOME USE )'),
			(2031, '2', '05', '02', '99', '000', 'ALAT RUMAH TANGGA LAINNYA'),
			(2032, '2', '05', '02', '99', '999', 'ALAT RUMAH TANGGA LAINNYA'),
			(2033, '2', '06', '00', '00', '000', '\"ALAT STUDIO'),
			(2034, '2', '06', '01', '00', '000', 'ALAT STUDIO'),
			(2035, '2', '06', '01', '01', '000', 'PERALATAN STUDIO AUDIO'),
			(2036, '2', '06', '01', '01', '001', 'AUDIO MIXING CONSOLE'),
			(2037, '2', '06', '01', '01', '002', 'AUDIO MIXING PORTABLE'),
			(2038, '2', '06', '01', '01', '003', 'AUDIO MIXING STATIONER'),
			(2039, '2', '06', '01', '01', '004', 'AUDIO ATTENUATOR'),
			(2040, '2', '06', '01', '01', '005', 'AUDIO AMPLIFIER'),
			(2041, '2', '06', '01', '01', '006', 'AUDIO ERASE UNIT'),
			(2042, '2', '06', '01', '01', '007', 'AUDIO VIDEO SELECTOR (PERALATAN STUDIO AUDIO)'),
			(2043, '2', '06', '01', '01', '008', 'AUDIO MONITOR ACTIVE'),
			(2044, '2', '06', '01', '01', '009', 'AUDIO MONITOR PASSIVE'),
			(2045, '2', '06', '01', '01', '010', 'AUDIO REVERBERATION'),
			(2046, '2', '06', '01', '01', '011', 'AUDIO PATCH PANEL'),
			(2047, '2', '06', '01', '01', '012', 'AUDIO DISTRIBUTION'),
			(2048, '2', '06', '01', '01', '013', 'AUDIO TONE GENERATOR'),
			(2049, '2', '06', '01', '01', '014', 'AUDIO CATRIDGE RECORDER'),
			(2050, '2', '06', '01', '01', '015', 'AUDIO LOGGING RECORDER'),
			(2051, '2', '06', '01', '01', '016', 'COMPACT DISC PLAYER'),
			(2052, '2', '06', '01', '01', '017', 'CASSETTE DUPLICATOR'),
			(2053, '2', '06', '01', '01', '018', 'DISC RECORD PLAYER'),
			(2054, '2', '06', '01', '01', '019', 'MULTITRACK RECORDER'),
			(2055, '2', '06', '01', '01', '020', 'REEL TAPE DUPLICATOR'),
			(2056, '2', '06', '01', '01', '021', 'COMPACT DISC JUKE BOX SYSTEM'),
			(2057, '2', '06', '01', '01', '022', 'TELEPHONE HYBRID'),
			(2058, '2', '06', '01', '01', '023', 'AUDIO PHONE IN'),
			(2059, '2', '06', '01', '01', '024', 'PROFANITY DELAY SYSTEM'),
			(2060, '2', '06', '01', '01', '025', 'AUDIO VISUAL'),
			(2061, '2', '06', '01', '01', '026', 'AUDIO FILTER'),
			(2062, '2', '06', '01', '01', '027', 'AUDIO LIMITER'),
			(2063, '2', '06', '01', '01', '028', 'AUDIO COMPRESSOR'),
			(2064, '2', '06', '01', '01', '029', 'TURN TABLE'),
			(2065, '2', '06', '01', '01', '030', 'TALK BACK UNIT'),
			(2066, '2', '06', '01', '01', '031', 'INTERCOM UNIT'),
			(2067, '2', '06', '01', '01', '032', 'BUZZER'),
			(2068, '2', '06', '01', '01', '033', 'SET STUDIO LIGHT SIGNAL'),
			(2069, '2', '06', '01', '01', '034', 'DOLBY NOISE REDUCTION'),
			(2070, '2', '06', '01', '01', '035', 'MODULATION MONITOR SPEAKER KABARET'),
			(2071, '2', '06', '01', '01', '036', 'MICROPHONE/WIRELESS MIC'),
			(2072, '2', '06', '01', '01', '037', 'MICROPHONE/BOOM STAND'),
			(2073, '2', '06', '01', '01', '038', 'MICROPHONE CONNECTOR BOX'),
			(2074, '2', '06', '01', '01', '039', 'LIGHT SIGNAL'),
			(2075, '2', '06', '01', '01', '040', 'POWER SUPPLY MICROPHONE'),
			(2076, '2', '06', '01', '01', '041', 'PROFESSIONAL SOUND SYSTEM'),
			(2077, '2', '06', '01', '01', '042', 'AUDIO MASTER CONTROL UNIT'),
			(2078, '2', '06', '01', '01', '043', 'TIME INDETIFICATION UNIT'),
			(2079, '2', '06', '01', '01', '044', 'AUDIO ANNOUNCER DESK'),
			(2080, '2', '06', '01', '01', '045', 'MASTER CLOCK (PERALATAN STUDIO AUDIO)'),
			(2081, '2', '06', '01', '01', '046', 'SLAVE CLOCK (PERALATAN STUDIO AUDIO)'),
			(2082, '2', '06', '01', '01', '047', 'AUDIO COMMAND DESK'),
			(2083, '2', '06', '01', '01', '048', 'UNINTERRUPTIBLE POWER SUPPLY (UPS)'),
			(2084, '2', '06', '01', '01', '049', 'MASTER CONTROL DESK'),
			(2085, '2', '06', '01', '01', '050', 'HEAD COMPENSATOR'),
			(2086, '2', '06', '01', '01', '051', 'AUTOMATIC VOLTAGE REGULATOR (AVR)'),
			(2087, '2', '06', '01', '01', '053', 'HUM/CABLE CONPENSATOR'),
			(2088, '2', '06', '01', '01', '054', 'EDITING & DUBBING SYSTEM'),
			(2089, '2', '06', '01', '01', '055', 'ANALOG DELAY (PERALATAN STUDIO AUDIO)'),
			(2090, '2', '06', '01', '01', '056', 'BATTERY CHARGER (PERALATAN STUDIO AUDIO)'),
			(2091, '2', '06', '01', '01', '057', 'BLANK PANEL'),
			(2092, '2', '06', '01', '01', '058', 'CONTROL UNIT HF'),
			(2093, '2', '06', '01', '01', '059', 'DELAY UNIT'),
			(2094, '2', '06', '01', '01', '060', 'POWER AMPLIFIER'),
			(2095, '2', '06', '01', '01', '061', 'PAGING MIC'),
			(2096, '2', '06', '01', '01', '062', 'COMPACT MONITOR PANEL FOR STEREO'),
			(2097, '2', '06', '01', '01', '063', 'PISTOL GRIP'),
			(2098, '2', '06', '01', '01', '064', 'MOUNTING BREAKEN'),
			(2099, '2', '06', '01', '01', '065', 'CHAIRMAN/AUDIO CONFERENCE'),
			(2100, '2', '06', '01', '01', '066', 'TIME SWITCHING'),
			(2101, '2', '06', '01', '01', '067', 'TERMINAL BOARD'),
			(2102, '2', '06', '01', '01', '068', 'ENCODER/DECODER'),
			(2103, '2', '06', '01', '01', '069', 'WIND SHIELD'),
			(2104, '2', '06', '01', '01', '070', 'RECEIVER HF/LF'),
			(2105, '2', '06', '01', '01', '071', 'RECEIVER VHF/FM'),
			(2106, '2', '06', '01', '01', '072', 'AUDIO TAPE REEL RECORDER'),
			(2107, '2', '06', '01', '01', '073', 'AUDIO CASSETTE RECORDER'),
			(2108, '2', '06', '01', '01', '074', 'COMPACT DISC RECORDER'),
			(2109, '2', '06', '01', '01', '075', 'DIGITAL AUDIO STORAGE SYSTEM'),
			(2110, '2', '06', '01', '01', '076', 'DIGITAL AUDIO TAPERECORDER'),
			(2111, '2', '06', '01', '01', '077', 'BLITZZER'),
			(2112, '2', '06', '01', '01', '078', 'AUDIO MAXIMIZER'),
			(2113, '2', '06', '01', '01', '079', 'MICROPHONE CABLE'),
			(2114, '2', '06', '01', '01', '080', 'SIGNAL INSTRUMENT SWITCER'),
			(2115, '2', '06', '01', '01', '081', 'CELLING MOUNT BRACKET'),
			(2116, '2', '06', '01', '01', '082', 'INTERFACEBOARD'),
			(2117, '2', '06', '01', '01', '083', 'VIDEO PRESENTER'),
			(2118, '2', '06', '01', '01', '084', 'MULTISCAN PROYECTOR'),
			(2119, '2', '06', '01', '01', '085', 'CABLE'),
			(2120, '2', '06', '01', '01', '086', '\"SCANNER COIR'),
			(2121, '2', '06', '01', '01', '087', 'KOMP. INTERFACE BOAR'),
			(2122, '2', '06', '01', '01', '088', 'VOICE RECORDER'),
			(2123, '2', '06', '01', '01', '089', 'AM/FM MEASUREMENT'),
			(2124, '2', '06', '01', '01', '090', 'SIGNAL ON AIR'),
			(2125, '2', '06', '01', '01', '091', 'DIGITAL LED RUNNING TEXT'),
			(2126, '2', '06', '01', '01', '092', 'ANALOG/DIGITAL RECEIVER'),
			(2127, '2', '06', '01', '01', '093', 'DIGITAL KEYBOARD TECHNICS'),
			(2128, '2', '06', '01', '01', '094', 'EXPLORIST 600'),
			(2129, '2', '06', '01', '01', '999', 'PERALATAN STUDIO AUDIO LAINNYA'),
			(2130, '2', '06', '01', '02', '000', 'PERALATAN STUDIO VIDEO DAN FILM'),
			(2131, '2', '06', '01', '02', '001', 'ASSIGNMENT SWITCHER'),
			(2132, '2', '06', '01', '02', '002', 'OFF AIR TV MONITOR'),
			(2133, '2', '06', '01', '02', '003', 'CAMERA ELECTRONIC'),
			(2134, '2', '06', '01', '02', '004', 'PULSE GENERATOR (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2135, '2', '06', '01', '02', '005', 'PULSE DISTRIBUTION AMPLIFIER'),
			(2136, '2', '06', '01', '02', '006', 'PULSE SWITCHER'),
			(2137, '2', '06', '01', '02', '007', 'PULSE DELAY LINE'),
			(2138, '2', '06', '01', '02', '008', 'CHARACTER GENERATOR (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2139, '2', '06', '01', '02', '009', 'CAPTION GENERATOR'),
			(2140, '2', '06', '01', '02', '010', 'TELECINE'),
			(2141, '2', '06', '01', '02', '011', 'VIDEO DISTRIBUTION AMPLIFIER'),
			(2142, '2', '06', '01', '02', '012', 'VIDEO MONITOR'),
			(2143, '2', '06', '01', '02', '013', 'VIDEO TAPE RECORDER PORTABLE'),
			(2144, '2', '06', '01', '02', '014', 'VIDEO TAPE RECORDER STATIONER'),
			(2145, '2', '06', '01', '02', '015', 'VIDEO MIXER'),
			(2146, '2', '06', '01', '02', '016', 'VIDEO SWITCHER'),
			(2147, '2', '06', '01', '02', '017', 'VIDEO EQUALIZER AMPLIFIER'),
			(2148, '2', '06', '01', '02', '018', 'VIDEO COLOR BAR GENERATOR'),
			(2149, '2', '06', '01', '02', '019', 'VIDEO CROSS BAR SWITCH'),
			(2150, '2', '06', '01', '02', '020', 'VIDEO TEST SIGNAL GENERATOR'),
			(2151, '2', '06', '01', '02', '021', 'VIDEO CORRECTOR'),
			(2152, '2', '06', '01', '02', '022', 'VIDEO CAPTION ADDER'),
			(2153, '2', '06', '01', '02', '023', 'VIDEO HUM COMPENSATOR'),
			(2154, '2', '06', '01', '02', '024', 'VIDEO PROCESSOR'),
			(2155, '2', '06', '01', '02', '025', 'VIDEO STATION ID GENERATOR'),
			(2156, '2', '06', '01', '02', '026', 'VIDEO PATCH PANEL'),
			(2157, '2', '06', '01', '02', '027', 'VIDEO DELAY UNIT'),
			(2158, '2', '06', '01', '02', '028', 'VIDEO PROCESSING AMPLIFIER'),
			(2159, '2', '06', '01', '02', '029', 'VIDEO EQUALIZER'),
			(2160, '2', '06', '01', '02', '030', 'VIDEO TAPE EVALUATOR'),
			(2161, '2', '06', '01', '02', '031', 'VIDEO EFFECT GENERATOR'),
			(2162, '2', '06', '01', '02', '032', 'VITS INSERTER GENERATOR'),
			(2163, '2', '06', '01', '02', '033', 'CAMERA WALL BOX'),
			(2164, '2', '06', '01', '02', '034', 'TELEPROMPTER'),
			(2165, '2', '06', '01', '02', '035', 'TIME BASE CORRECTOR'),
			(2166, '2', '06', '01', '02', '036', 'GUN SMOKE'),
			(2167, '2', '06', '01', '02', '037', 'AUTOMATIC EDITING CONTROL (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2168, '2', '06', '01', '02', '038', 'POWER SUPPLY (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2169, '2', '06', '01', '02', '039', 'EDITING ELECTRONIC'),
			(2170, '2', '06', '01', '02', '040', 'RECTIFIER UNIT'),
			(2171, '2', '06', '01', '02', '041', 'REMOTE CONTROL UNIT'),
			(2172, '2', '06', '01', '02', '042', 'RAK PERALATAN'),
			(2173, '2', '06', '01', '02', '043', 'STABILIZING AMPLIFIER'),
			(2174, '2', '06', '01', '02', '044', 'DIGITAL VIDEO EFFECT'),
			(2175, '2', '06', '01', '02', '045', 'TRIPOD CAMERA'),
			(2176, '2', '06', '01', '02', '046', 'DIMMER'),
			(2177, '2', '06', '01', '02', '047', 'CHILLER'),
			(2178, '2', '06', '01', '02', '048', 'SLAVE CLOCK (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2179, '2', '06', '01', '02', '049', 'MASTER CLOCK (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2180, '2', '06', '01', '02', '050', 'TELEDYNE'),
			(2181, '2', '06', '01', '02', '051', 'FLYING SPOT SCANNER'),
			(2182, '2', '06', '01', '02', '052', 'SYNCHRONIZING PULSE GENERATOR'),
			(2183, '2', '06', '01', '02', '053', 'DC CONVERTER'),
			(2184, '2', '06', '01', '02', '054', 'BLACK BURST GENERATOR'),
			(2185, '2', '06', '01', '02', '055', 'LIGHTING STAND TRIPOD'),
			(2186, '2', '06', '01', '02', '056', 'FILM PROJECTOR'),
			(2187, '2', '06', '01', '02', '057', 'SLIDE PROJECTOR'),
			(2188, '2', '06', '01', '02', '058', 'COMMAND DESK'),
			(2189, '2', '06', '01', '02', '059', 'ANNOUNCER DESK'),
			(2190, '2', '06', '01', '02', '060', 'CAMERA FILM'),
			(2191, '2', '06', '01', '02', '061', 'LENSA KAMERA'),
			(2192, '2', '06', '01', '02', '062', 'FILM MAGAZINE'),
			(2193, '2', '06', '01', '02', '063', 'CLAPER'),
			(2194, '2', '06', '01', '02', '064', 'CHANGING BAG'),
			(2195, '2', '06', '01', '02', '065', 'CONDITIONER'),
			(2196, '2', '06', '01', '02', '066', 'COLOUR FILM ANALYZER'),
			(2197, '2', '06', '01', '02', '068', 'FILM SOUND RECORDER'),
			(2198, '2', '06', '01', '02', '069', 'TELE RECORDER'),
			(2199, '2', '06', '01', '02', '070', 'CAMERA VIEW FINDER'),
			(2200, '2', '06', '01', '02', '071', 'SERVO ZOOM LENS'),
			(2201, '2', '06', '01', '02', '072', 'CAMERA ADAPTOR'),
			(2202, '2', '06', '01', '02', '073', 'PHOTO PROCESSING SET'),
			(2203, '2', '06', '01', '02', '074', 'MICRO FILM'),
			(2204, '2', '06', '01', '02', '075', 'MIXER PVC'),
			(2205, '2', '06', '01', '02', '076', 'UNIT REPLENIESER TANK'),
			(2206, '2', '06', '01', '02', '077', 'HORIZONTAL MOTORIZED FILM REWINDER'),
			(2207, '2', '06', '01', '02', '078', 'VERTICAL MOTORIZED FILM REWINDER'),
			(2208, '2', '06', '01', '02', '079', 'MANUAL FILM REWINDER'),
			(2209, '2', '06', '01', '02', '080', 'MESIN PROSESING FILM NEGATIF'),
			(2210, '2', '06', '01', '02', '081', 'MESIN PROSESING FILM POSITIF'),
			(2211, '2', '06', '01', '02', '082', 'MESIN PROSESING FILM WARNA NEGATIF (ECN)'),
			(2212, '2', '06', '01', '02', '083', 'MESIN PROSESING FILM WARNA POSITIF (ECP)'),
			(2213, '2', '06', '01', '02', '084', 'MESIN FILM COLOR ANALYZER'),
			(2214, '2', '06', '01', '02', '085', 'ANALITICAL BALANCE (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2215, '2', '06', '01', '02', '086', 'ALAT PEMANAS PROSESING ( WATER HEATER )'),
			(2216, '2', '06', '01', '02', '087', 'STAPLER FILM'),
			(2217, '2', '06', '01', '02', '088', 'MAGNETIC STIP'),
			(2218, '2', '06', '01', '02', '089', 'SPLITZER TAPE'),
			(2219, '2', '06', '01', '02', '090', 'MEJA EDITING FILM'),
			(2220, '2', '06', '01', '02', '091', 'DIGITAL TBC'),
			(2221, '2', '06', '01', '02', '092', 'TITANIUM TANK SINGLE SHAFT'),
			(2222, '2', '06', '01', '02', '093', 'TEMPERATUR CONTROL C/W'),
			(2223, '2', '06', '01', '02', '094', 'GEAR BOX SUN ASSY'),
			(2224, '2', '06', '01', '02', '095', 'TACHO GENERATOR FOR DRIVE MOTOR RACHING'),
			(2225, '2', '06', '01', '02', '096', 'CIRCULATION SYSTEM COMPLET'),
			(2226, '2', '06', '01', '02', '097', 'CHILLER WATER COMPLET'),
			(2227, '2', '06', '01', '02', '098', 'VIDEO AUDIO JACK PANEL'),
			(2228, '2', '06', '01', '02', '099', 'AUTOMATIC EMERGENCY LIGHT'),
			(2229, '2', '06', '01', '02', '100', 'FILM CHAIN MULTIPLIER'),
			(2230, '2', '06', '01', '02', '101', 'PHOTO TUSTEL'),
			(2231, '2', '06', '01', '02', '102', 'PHOTO TUSTEL POLAROID'),
			(2232, '2', '06', '01', '02', '103', 'BETACAM RECORDER/PLAYER'),
			(2233, '2', '06', '01', '02', '104', 'SLIDE RAIL'),
			(2234, '2', '06', '01', '02', '105', 'WEAPON & METAL DETECTOR ( CHECK GATE )'),
			(2235, '2', '06', '01', '02', '107', 'LAYAR FILM/PROJECTOR'),
			(2236, '2', '06', '01', '02', '108', 'CAMERA TUNE SIMULATOR'),
			(2237, '2', '06', '01', '02', '109', 'DRY SPLITZER FILM'),
			(2238, '2', '06', '01', '02', '110', 'VIDEO TONE CLEANER'),
			(2239, '2', '06', '01', '02', '111', 'MINI VIEWER'),
			(2240, '2', '06', '01', '02', '112', 'PUSH BUTTON CONTROL PANEL'),
			(2241, '2', '06', '01', '02', '113', 'RAK TERMINAL VENCING'),
			(2242, '2', '06', '01', '02', '114', 'STANDARD TRUE SIGNAL/MASTER RACK'),
			(2243, '2', '06', '01', '02', '115', 'MOTOR DRIVER'),
			(2244, '2', '06', '01', '02', '116', 'ANALOG DELAY (PERALATAN STUDIO VIDEO DAN FILM)'),
			(2245, '2', '06', '01', '02', '117', 'STANDARD POINT ANIMATION'),
			(2246, '2', '06', '01', '02', '118', 'HEAD SET'),
			(2247, '2', '06', '01', '02', '119', 'CHARACTER EFFECT INTERFACE'),
			(2248, '2', '06', '01', '02', '120', 'LIGHTING HEAD BODY'),
			(2249, '2', '06', '01', '02', '121', 'LIGHTING MECHANIC'),
			(2250, '2', '06', '01', '02', '122', 'ALOS 321 FICHE READER'),
			(2251, '2', '06', '01', '02', '123', 'ALOS 321 ALOS READER'),
			(2252, '2', '06', '01', '02', '124', 'INSERTER JACKET FILMNES MODEL FRF-160 & 3500'),
			(2253, '2', '06', '01', '02', '125', '\"DIASO PRINTER'),
			(2254, '2', '06', '01', '02', '126', 'DIASO PROCESSOR 404 DAN 404 D'),
			(2255, '2', '06', '01', '02', '127', 'CAMERA UNDER WATER'),
			(2256, '2', '06', '01', '02', '128', 'CAMERA DIGITAL'),
			(2257, '2', '06', '01', '02', '129', 'TAS KAMERA'),
			(2258, '2', '06', '01', '02', '130', 'LAMPU BLITZ KAMERA'),
			(2259, '2', '06', '01', '02', '131', 'LENSA FILTER'),
			(2260, '2', '06', '01', '02', '132', 'VIDEO CONFERENCE'),
			(2261, '2', '06', '01', '02', '133', 'TURBO IDDR (INTELLIGENT DIGITAL DISK RECORDER)'),
			(2262, '2', '06', '01', '02', '134', 'VIDEO ROUTER'),
			(2263, '2', '06', '01', '02', '135', 'LCD MONITOR'),
			(2264, '2', '06', '01', '02', '136', 'SDI RASTERISER'),
			(2265, '2', '06', '01', '02', '137', 'AUDIO MONITORING UNIT'),
			(2266, '2', '06', '01', '02', '138', 'FRAME SYNCHRONIZER'),
			(2267, '2', '06', '01', '02', '139', 'AUDIO TRANSCODER'),
			(2268, '2', '06', '01', '02', '140', 'AUDIO CONVERTER'),
			(2269, '2', '06', '01', '02', '141', 'AUTOMATION MAIN'),
			(2270, '2', '06', '01', '02', '142', 'RECORDING WORKSTATION'),
			(2271, '2', '06', '01', '02', '143', 'EDITOR WORKSTATION'),
			(2272, '2', '06', '01', '02', '144', 'ON AIR RECORDING'),
			(2273, '2', '06', '01', '02', '145', 'CONNECTORS'),
			(2274, '2', '06', '01', '02', '146', 'PATCH CORD'),
			(2275, '2', '06', '01', '02', '147', 'AUDIO EMBEDDER'),
			(2276, '2', '06', '01', '02', '148', 'VTR RECORDER'),
			(2277, '2', '06', '01', '02', '149', 'ANALOG VIDEO ROUTER'),
			(2278, '2', '06', '01', '02', '150', 'BROADBAND AMLIFIER'),
			(2279, '2', '06', '01', '02', '151', 'SPLITTER'),
			(2280, '2', '06', '01', '02', '152', 'RF CABLE'),
			(2281, '2', '06', '01', '02', '153', 'F CONNECTOR'),
			(2282, '2', '06', '01', '02', '154', 'TV CONNECTOR'),
			(2283, '2', '06', '01', '02', '155', 'THERMO BIND MACHINE'),
			(2284, '2', '06', '01', '02', '156', 'KAMERA STILE'),
			(2285, '2', '06', '01', '02', '157', 'MINI DV'),
			(2286, '2', '06', '01', '02', '158', 'MONOPOD'),
			(2287, '2', '06', '01', '02', '159', 'CLIPP ON'),
			(2288, '2', '06', '01', '02', '160', 'COMPUTER EDITING'),
			(2289, '2', '06', '01', '02', '161', 'CUT EDITING'),
			(2290, '2', '06', '01', '02', '162', 'DUPLICATOR VCD'),
			(2291, '2', '06', '01', '02', '163', 'DUPLICATOR DVD'),
			(2292, '2', '06', '01', '02', '164', 'VIDEO SPLITTER'),
			(2293, '2', '06', '01', '02', '165', 'CAMERA CONFERENCE'),
			(2294, '2', '06', '01', '02', '999', 'PERALATAN STUDIO VIDEO DAN FILM LAINNYA'),
			(2295, '2', '06', '01', '03', '000', 'PERALATAN STUDIO GAMBAR'),
			(2296, '2', '06', '01', '03', '001', 'MEJA GAMBAR'),
			(2297, '2', '06', '01', '03', '002', 'LICHDRUCK APPARAAT'),
			(2298, '2', '06', '01', '03', '003', 'SABLON SET'),
			(2299, '2', '06', '01', '03', '004', 'ALAT TULIS GAMBAR'),
			(2300, '2', '06', '01', '03', '005', 'BUSUR GAMBAR'),
			(2301, '2', '06', '01', '03', '006', 'JANGKA GAMBAR'),
			(2302, '2', '06', '01', '03', '999', 'PERALATAN STUDIO GAMBAR LAINNYA'),
			(2303, '2', '06', '01', '04', '000', 'PERALATAN CETAK'),
			(2304, '2', '06', '01', '04', '001', 'MEJA MEMBUAT KLISE'),
			(2305, '2', '06', '01', '04', '002', 'MEJA CETAK TANGAN'),
			(2306, '2', '06', '01', '04', '003', 'MESIN CETAK LISTRIK SHEET'),
			(2307, '2', '06', '01', '04', '004', 'MESIN CETAK LISTRIK ROLL'),
			(2308, '2', '06', '01', '04', '005', 'MESIN CETAK ELEKTRONIK'),
			(2309, '2', '06', '01', '04', '006', 'MESIN CETAK'),
			(2310, '2', '06', '01', '04', '007', 'MESIN CETAK OFFSET SHEET'),
			(2311, '2', '06', '01', '04', '008', 'MESIN CETAK OFFSET ROLL'),
			(2312, '2', '06', '01', '04', '009', 'MESIN CETAK OFFSET MINI'),
			(2313, '2', '06', '01', '04', '010', 'MESIN PEMOTONG BIASA'),
			(2314, '2', '06', '01', '04', '011', 'MESIN PEMOTONG BIASA TIGA PISAU'),
			(2315, '2', '06', '01', '04', '012', 'MESIN JILID BUNDAR'),
			(2316, '2', '06', '01', '04', '013', 'MESIN JILID BESAR'),
			(2317, '2', '06', '01', '04', '014', 'MESIN JILID'),
			(2318, '2', '06', '01', '04', '015', 'MESIN LIPAT'),
			(2319, '2', '06', '01', '04', '016', 'MESIN PEMBUAT HURUF'),
			(2320, '2', '06', '01', '04', '017', 'MESIN PENYUSUN HURUF BIASA'),
			(2321, '2', '06', '01', '04', '018', 'MESIN PENYUSUN HURUF FOTO (FOTO TYPE SETTING)'),
			(2322, '2', '06', '01', '04', '019', 'MESIN PELUBANG (PERALATAN CETAK)'),
			(2323, '2', '06', '01', '04', '020', 'MESIN PROOF'),
			(2324, '2', '06', '01', '04', '021', 'CAMERA VERTICAL'),
			(2325, '2', '06', '01', '04', '022', 'MESIN PRES'),
			(2326, '2', '06', '01', '04', '023', 'MESIN JAHIT KAWAT'),
			(2327, '2', '06', '01', '04', '024', 'MESIN JAHIT BENANG'),
			(2328, '2', '06', '01', '04', '025', 'MESIN PILUNG'),
			(2329, '2', '06', '01', '04', '026', 'MESIN GARIS'),
			(2330, '2', '06', '01', '04', '027', 'MESIN PEREKAM STENSIL FOLIO'),
			(2331, '2', '06', '01', '04', '028', 'MESIN PEREKAM STENSIL DOUBLE FOLIO'),
			(2332, '2', '06', '01', '04', '029', 'MESIN PLATE MAKER FOLIO'),
			(2333, '2', '06', '01', '04', '030', 'MESIN PLATE MAKER DOUBLE FOLIO'),
			(2334, '2', '06', '01', '04', '031', 'MESIN POTONG'),
			(2335, '2', '06', '01', '04', '032', 'MESIN HANDPRESS'),
			(2336, '2', '06', '01', '04', '033', 'MESIN STAHD'),
			(2337, '2', '06', '01', '04', '034', 'MESIN KERTAS'),
			(2338, '2', '06', '01', '04', '035', 'KACIP POTONG SUDUT'),
			(2339, '2', '06', '01', '04', '036', 'ALAT PEMBUAT VORMSTAND'),
			(2340, '2', '06', '01', '04', '037', 'MESIN PASET'),
			(2341, '2', '06', '01', '04', '038', 'MESIN PRASISE KLISE'),
			(2342, '2', '06', '01', '04', '039', 'MESIN PEMBOLONG FILM SETENGAH PLANO'),
			(2343, '2', '06', '01', '04', '040', 'MESIN CETAK MAS'),
			(2344, '2', '06', '01', '04', '041', 'MESIN CETAK STEREO TYPER'),
			(2345, '2', '06', '01', '04', '042', 'MESIN CETAK BRAILLE'),
			(2346, '2', '06', '01', '04', '043', 'MESIN FONDS'),
			(2347, '2', '06', '01', '04', '044', 'MESIN FOLDING'),
			(2348, '2', '06', '01', '04', '045', 'MESIN BARCODE'),
			(2349, '2', '06', '01', '04', '046', 'MESIN PROFESIONAL VELOBINDER'),
			(2350, '2', '06', '01', '04', '047', 'MESIN CACAH'),
			(2351, '2', '06', '01', '04', '048', 'IMAGE SETTER'),
			(2352, '2', '06', '01', '04', '049', 'MESIN SPARASI'),
			(2353, '2', '06', '01', '04', '050', 'CAMERA HORIZONTAL'),
			(2354, '2', '06', '01', '04', '051', 'ALAT COVER CREASING'),
			(2355, '2', '06', '01', '04', '052', 'MESIN PEMBUAT ID CARD'),
			(2356, '2', '06', '01', '04', '999', 'PERALATAN CETAK LAINNYA'),
			(2357, '2', '06', '01', '05', '000', 'PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH'),
			(2358, '2', '06', '01', '05', '001', 'AUTOGRAPH UNIT'),
			(2359, '2', '06', '01', '05', '002', 'AVIOGRAPH PLUS PLOTING TABLE'),
			(2360, '2', '06', '01', '05', '003', 'PLANITOP'),
			(2361, '2', '06', '01', '05', '004', 'POINT TRANTER DEVICE'),
			(2362, '2', '06', '01', '05', '005', 'TRESTIRIAL CAMERA'),
			(2363, '2', '06', '01', '05', '006', 'SLOHED TEMLET'),
			(2364, '2', '06', '01', '05', '007', 'SKETCH MASTER'),
			(2365, '2', '06', '01', '05', '008', 'RECTIFIER (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2366, '2', '06', '01', '05', '009', 'OPTICAL PANTOGRAPH'),
			(2367, '2', '06', '01', '05', '010', 'CONTACT PRINTER'),
			(2368, '2', '06', '01', '05', '011', 'PENGERING PHOTO'),
			(2369, '2', '06', '01', '05', '012', 'VACUM FRAME'),
			(2370, '2', '06', '01', '05', '013', 'COORDINATOGRAPH'),
			(2371, '2', '06', '01', '05', '014', 'PEMOTONG FILM'),
			(2372, '2', '06', '01', '05', '015', 'STREOSCOPE TANAH'),
			(2373, '2', '06', '01', '05', '016', 'WATERPAS'),
			(2374, '2', '06', '01', '05', '017', 'THEODOLITE (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2375, '2', '06', '01', '05', '018', 'DISTOMAT'),
			(2376, '2', '06', '01', '05', '019', 'B.T.M'),
			(2377, '2', '06', '01', '05', '020', 'LEVEL'),
			(2378, '2', '06', '01', '05', '021', 'JALON'),
			(2379, '2', '06', '01', '05', '022', 'RAMBU/BAK UKUR'),
			(2380, '2', '06', '01', '05', '023', 'KOMPAS GEOLOGI'),
			(2381, '2', '06', '01', '05', '024', 'CLINOMETER'),
			(2382, '2', '06', '01', '05', '025', 'ALTIMETER (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2383, '2', '06', '01', '05', '026', 'HOLIOMETER'),
			(2384, '2', '06', '01', '05', '027', 'TELESCOPE (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2385, '2', '06', '01', '05', '028', 'PASSER DOSS'),
			(2386, '2', '06', '01', '05', '029', 'CURVERMETER'),
			(2387, '2', '06', '01', '05', '030', 'ROLLMETER'),
			(2388, '2', '06', '01', '05', '031', 'MEET BAND'),
			(2389, '2', '06', '01', '05', '032', 'BUSUR DERAJAT'),
			(2390, '2', '06', '01', '05', '033', 'CHRONOMETER (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2391, '2', '06', '01', '05', '034', 'GAWANG LOKASI'),
			(2392, '2', '06', '01', '05', '035', 'KOMPAS (PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH)'),
			(2393, '2', '06', '01', '05', '036', 'SEXTANT'),
			(2394, '2', '06', '01', '05', '037', 'TEROPONG/KEKER'),
			(2395, '2', '06', '01', '05', '038', 'GPS RECEIVER'),
			(2396, '2', '06', '01', '05', '039', 'GROUND PARETRATING RADAR'),
			(2397, '2', '06', '01', '05', '040', 'TEKEN SCHAAL/JANGKA TUSUK'),
			(2398, '2', '06', '01', '05', '041', 'PANTOGRAPH'),
			(2399, '2', '06', '01', '05', '042', 'PLANI METER'),
			(2400, '2', '06', '01', '05', '043', 'PRISMA ROELAK'),
			(2401, '2', '06', '01', '05', '044', 'PRISMA METER'),
			(2402, '2', '06', '01', '05', '045', 'PRISMA UKUR'),
			(2403, '2', '06', '01', '05', '046', 'RUITER PLAAT'),
			(2404, '2', '06', '01', '05', '047', 'KAMERA UDARA'),
			(2405, '2', '06', '01', '05', '048', 'STEREOPLOTTER'),
			(2406, '2', '06', '01', '05', '049', 'PLANICOMP'),
			(2407, '2', '06', '01', '05', '050', 'MEJA SINAR'),
			(2408, '2', '06', '01', '05', '051', 'GRAVER'),
			(2409, '2', '06', '01', '05', '052', 'PEN HOLDER'),
			(2410, '2', '06', '01', '05', '999', 'PERALATAN STUDIO PEMETAAN/PERALATAN UKUR TANAH LAINNYA'),
			(2411, '2', '06', '01', '99', '000', 'ALAT STUDIO LAINNYA'),
			(2412, '2', '06', '01', '99', '999', 'ALAT STUDIO LAINNYA'),
			(2413, '2', '06', '02', '00', '000', 'ALAT KOMUNIKASI'),
			(2414, '2', '06', '02', '01', '000', 'ALAT KOMUNIKASI TELEPHONE'),
			(2415, '2', '06', '02', '01', '001', 'TELEPHONE (PABX)'),
			(2416, '2', '06', '02', '01', '002', 'INTERMEDIATE TELEPHONE/KEY TELEPHONE'),
			(2417, '2', '06', '02', '01', '003', 'PESAWAT TELEPHONE'),
			(2418, '2', '06', '02', '01', '004', 'TELEPHONE MOBILE'),
			(2419, '2', '06', '02', '01', '005', 'PAGER'),
			(2420, '2', '06', '02', '01', '006', 'HANDY TALKY (HT)'),
			(2421, '2', '06', '02', '01', '007', 'TELEX'),
			(2422, '2', '06', '02', '01', '008', 'SELECTIVE COLLING'),
			(2423, '2', '06', '02', '01', '009', 'PERALATAN SPECH PLAS'),
			(2424, '2', '06', '02', '01', '010', 'FACSIMILE'),
			(2425, '2', '06', '02', '01', '011', 'BIDDING PIT'),
			(2426, '2', '06', '02', '01', '012', 'LOCAL BATTERY TELEPHONE'),
			(2427, '2', '06', '02', '01', '013', 'SENHUB FIXED'),
			(2428, '2', '06', '02', '01', '014', 'SENHUB MOBILE'),
			(2429, '2', '06', '02', '01', '015', 'TELEPON LAPANGAN'),
			(2430, '2', '06', '02', '01', '016', 'SENTRAL TELEPON LAPANGAN'),
			(2431, '2', '06', '02', '01', '017', 'TELEPON SATELIT'),
			(2432, '2', '06', '02', '01', '018', 'KOM DATA'),
			(2433, '2', '06', '02', '01', '019', 'PDA'),
			(2434, '2', '06', '02', '01', '020', 'TELEPON DIGITAL'),
			(2435, '2', '06', '02', '01', '021', 'TELEPON ANALOG'),
			(2436, '2', '06', '02', '01', '999', 'ALAT KOMUNIKASI TELEPHONE LAINNYA'),
			(2437, '2', '06', '02', '02', '000', 'ALAT KOMUNIKASI RADIO SSB'),
			(2438, '2', '06', '02', '02', '001', 'UNIT TRANCEIVER SSB PORTABLE'),
			(2439, '2', '06', '02', '02', '002', 'UNIT TRANCEIVER SSB TRANSPORTABLE'),
			(2440, '2', '06', '02', '02', '003', 'UNIT TRANCEIVER SSB STATIONERY'),
			(2441, '2', '06', '02', '02', '999', 'ALAT KOMUNIKASI RADIO SSB LAINNYA'),
			(2442, '2', '06', '02', '03', '000', 'ALAT KOMUNIKASI RADIO HF/FM'),
			(2443, '2', '06', '02', '03', '001', 'UNIT TRANCEIVER HF PORTABLE'),
			(2444, '2', '06', '02', '03', '002', 'UNIT TRANCEIVER HF TRANSPORTABLE'),
			(2445, '2', '06', '02', '03', '003', 'UNIT TRANCEIVER HF STATIONERY'),
			(2446, '2', '06', '02', '03', '004', 'UNIT TRANCEIVER FM'),
			(2447, '2', '06', '02', '03', '999', 'ALAT KOMUNIKASI RADIO HF/FM LAINNYA'),
			(2448, '2', '06', '02', '04', '000', 'ALAT KOMUNIKASI RADIO VHF'),
			(2449, '2', '06', '02', '04', '001', 'UNIT TRANCEIVER VHF PORTABLE'),
			(2450, '2', '06', '02', '04', '002', 'UNIT TRANCEIVER VHF TRANSPORTABLE'),
			(2451, '2', '06', '02', '04', '003', 'UNIT TRANCEIVER VHF STATIONARY'),
			(2452, '2', '06', '02', '04', '999', 'ALAT KOMUNIKASI RADIO VHF LAINNYA'),
			(2453, '2', '06', '02', '05', '000', 'ALAT KOMUNIKASI RADIO UHF'),
			(2454, '2', '06', '02', '05', '001', 'UNIT TRANCEIVER UHF PORTABLE'),
			(2455, '2', '06', '02', '05', '002', 'UNIT TRANCEIVER UHF TRANSPORTABLE'),
			(2456, '2', '06', '02', '05', '003', 'UNIT TRANCEIVER UHF STATIONARY'),
			(2457, '2', '06', '02', '05', '999', 'ALAT KOMUNIKASI RADIO UHF LAINNYA'),
			(2458, '2', '06', '02', '06', '000', 'ALAT KOMUNIKASI SOSIAL'),
			(2459, '2', '06', '02', '06', '001', 'PUBLIK ADDRESS (LAPANGAN)'),
			(2460, '2', '06', '02', '06', '002', 'WIRELESS AMPLIFIER'),
			(2461, '2', '06', '02', '06', '003', 'SLIDE PROJECTOR (LAPANGAN)'),
			(2462, '2', '06', '02', '06', '004', 'MULTIPLEX SYSTEM'),
			(2463, '2', '06', '02', '06', '005', 'FREQUENCY SYSTHESIZER UNIT'),
			(2464, '2', '06', '02', '06', '006', 'PATCHING BOARD'),
			(2465, '2', '06', '02', '06', '999', 'ALAT KOMUNIKASI SOSIAL LAINNYA'),
			(2466, '2', '06', '02', '07', '000', 'ALAT-ALAT SANDI'),
			(2467, '2', '06', '02', '07', '001', 'MORSE KEYER'),
			(2468, '2', '06', '02', '07', '002', 'AUTOMATIC DEORSE KEYER'),
			(2469, '2', '06', '02', '07', '003', 'ALAT SEMBOYAN'),
			(2470, '2', '06', '02', '07', '004', 'MESIN SANDI DAN KELENGKAPANNYA'),
			(2471, '2', '06', '02', '07', '005', 'FINGER PRINTER TIME AND ATTANDANCE ACCES CONTROL SYSTEM'),
			(2472, '2', '06', '02', '07', '006', 'MESIN SANDI TEKS'),
			(2473, '2', '06', '02', '07', '007', 'MESIN SANDI SUARA'),
			(2474, '2', '06', '02', '07', '008', 'MESIN SANDI DATA'),
			(2475, '2', '06', '02', '07', '009', 'MESIN SANDI BERBASIS SOFTWARE'),
			(2476, '2', '06', '02', '07', '010', 'MESIN SANDI BERBASIS HARDWARE'),
			(2477, '2', '06', '02', '07', '011', 'MESIN SANDI BERBASIS SOFTWARE DAN HARDWARE'),
			(2478, '2', '06', '02', '07', '012', 'ALAT PEMBANGKIT KUNCI'),
			(2479, '2', '06', '02', '07', '013', 'ALAT PENDISTRIBUSI KUNCI'),
			(2480, '2', '06', '02', '07', '014', 'CRYPTHOPONE'),
			(2481, '2', '06', '02', '07', '015', 'CRYTOFAX'),
			(2482, '2', '06', '02', '07', '016', 'SERVER ENCRIPTION'),
			(2483, '2', '06', '02', '07', '017', 'HANDPHONE ENCRIPTION'),
			(2484, '2', '06', '02', '07', '018', 'GSM JAMMER'),
			(2485, '2', '06', '02', '07', '019', 'CDMA JAMMER'),
			(2486, '2', '06', '02', '07', '999', 'ALAT-ALAT SANDI LAINNYA'),
			(2487, '2', '06', '02', '08', '000', 'ALAT KOMUNIKASI KHUSUS'),
			(2488, '2', '06', '02', '08', '001', 'ALAT DF RADIO SSB'),
			(2489, '2', '06', '02', '08', '002', 'SUPER BROOM'),
			(2490, '2', '06', '02', '08', '003', 'ALAT DF RADIO HF/FM'),
			(2491, '2', '06', '02', '08', '004', 'SCANLOCK PLUS CEBERUS'),
			(2492, '2', '06', '02', '08', '005', 'ALAT DF RADIO VHF'),
			(2493, '2', '06', '02', '08', '006', 'STELATH DIGITAL REPEATER'),
			(2494, '2', '06', '02', '08', '007', 'ALAT DF RADIO UHF'),
			(2495, '2', '06', '02', '08', '008', 'TRANKING'),
			(2496, '2', '06', '02', '08', '009', 'TELEPON TAPING'),
			(2497, '2', '06', '02', '08', '010', 'STELATH'),
			(2498, '2', '06', '02', '08', '011', 'VISATELIT'),
			(2499, '2', '06', '02', '08', '012', 'MAINFRAME (ALAT KOMUNIKASI KHUSUS)'),
			(2500, '2', '06', '02', '08', '013', 'SAFE LIGHT FILTER'),
			(2501, '2', '06', '02', '08', '014', 'ANTI SADAP TELEPON (SCANBLER)'),
			(2502, '2', '06', '02', '08', '015', 'BILLINF SYSTEM'),
			(2503, '2', '06', '02', '08', '016', 'ROOM MONITORING MC06'),
			(2504, '2', '06', '02', '08', '017', 'WATCH TRANSMITER'),
			(2505, '2', '06', '02', '08', '018', 'ASHTRAY'),
			(2506, '2', '06', '02', '08', '019', 'NON DIRECTION BEACON (NDB)'),
			(2507, '2', '06', '02', '08', '020', 'RADIO LINK'),
			(2508, '2', '06', '02', '08', '021', 'LOCALIZER'),
			(2509, '2', '06', '02', '08', '022', 'GLADE PATH'),
			(2510, '2', '06', '02', '08', '023', 'MIDLE MARKER'),
			(2511, '2', '06', '02', '08', '024', 'RADIO COMMUNICATION MATCHING SWITCH (RCMS)'),
			(2512, '2', '06', '02', '08', '025', 'DIRECTION VERY OMNI RANGE (DVOR)'),
			(2513, '2', '06', '02', '08', '026', 'INTEGRATED GROUND CAOMMUNICATION SYSTEM (SGRS)'),
			(2514, '2', '06', '02', '08', '027', 'SWITCHING GROUND RECEIVER SYSTEM (SGRS)'),
			(2515, '2', '06', '02', '08', '028', 'ALAT RX RADIO SSB'),
			(2516, '2', '06', '02', '08', '029', 'ALAT RX RADIO HF/FM'),
			(2517, '2', '06', '02', '08', '030', 'ALAT RX RADIO VHF'),
			(2518, '2', '06', '02', '08', '031', 'ALAT RX RADIO UHF'),
			(2519, '2', '06', '02', '08', '032', 'ALAT JAMMING RADIO SSB'),
			(2520, '2', '06', '02', '08', '033', 'ALAT JAMMING RADIO HF/FM'),
			(2521, '2', '06', '02', '08', '034', 'ALAT JAMMING RADIO VHF'),
			(2522, '2', '06', '02', '08', '035', 'ALAT JAMMING RADIO UHF'),
			(2523, '2', '06', '02', '08', '036', 'ALAT SPEKTRUM FREK MONITOR SSB'),
			(2524, '2', '06', '02', '08', '037', 'ALAT SPEKTRUM FREK MONITOR HF/FM'),
			(2525, '2', '06', '02', '08', '038', 'ALAT SPEKTRUM FREK MONITOR VHF'),
			(2526, '2', '06', '02', '08', '039', 'ALAT SPEKTRUM FREK MONITOR UHF'),
			(2527, '2', '06', '02', '08', '040', 'ALAT TRAFFIC ANALYSIS'),
			(2528, '2', '06', '02', '08', '041', 'ALAT COUNTERSURVEILLANCE'),
			(2529, '2', '06', '02', '08', '042', 'ALAT SURVEILLANCE'),
			(2530, '2', '06', '02', '08', '043', 'ALAT JAMMING FREKUENSI'),
			(2531, '2', '06', '02', '08', '044', 'ALAT PENGENDALI PANCARAN GELOMBANG ELEKTROMAGNETIK (TEMPEST)'),
			(2532, '2', '06', '02', '08', '999', 'ALAT KOMUNIKASI KHUSUS LAINNYA'),
			(2533, '2', '06', '02', '09', '000', 'ALAT KOMUNIKASI DIGITAL DAN KONVENSIONAL'),
			(2534, '2', '06', '02', '09', '001', 'SYSTEM CONTROL NODE MULTI SITE SYSTEM'),
			(2535, '2', '06', '02', '09', '002', 'SITE BASE STATAION MULTI SITE SYSTEM'),
			(2536, '2', '06', '02', '09', '003', 'CONTROLL CENTER'),
			(2537, '2', '06', '02', '09', '004', 'E2EENCRYPTION MANAGEMENT TOOLS'),
			(2538, '2', '06', '02', '09', '005', 'NETWORK MONITORING SYSTEM'),
			(2539, '2', '06', '02', '09', '006', 'SWITCHING MATRIX AND SERVER'),
			(2540, '2', '06', '02', '09', '007', 'DIGITAL RECORDING SYSTEM'),
			(2541, '2', '06', '02', '09', '008', 'OFFICIAL PHERIPHERAL'),
			(2542, '2', '06', '02', '09', '009', 'MOBILE UNIT'),
			(2543, '2', '06', '02', '09', '010', 'MOBILE GATEWAY'),
			(2544, '2', '06', '02', '09', '011', 'CONVERT BODY'),
			(2545, '2', '06', '02', '09', '012', 'REPEATER RX/TX'),
			(2546, '2', '06', '02', '09', '013', 'REPEATER MULTIBAND COMBINER 4 IN 4OUT'),
			(2547, '2', '06', '02', '09', '014', 'REPEATER MULTIBAND COMBINER 4 IN 2OUT'),
			(2548, '2', '06', '02', '09', '015', 'REPEATER MULTIBAND COMBINER 2 IN 2OUT'),
			(2549, '2', '06', '02', '09', '016', 'REPEATER CDMA 80PO MHZ'),
			(2550, '2', '06', '02', '09', '017', 'REPEATER CDS 1800 MHZ'),
			(2551, '2', '06', '02', '09', '999', 'ALAT KOMUNIKASI DIGITAL DAN KONVENSIONAL LAINNYA'),
			(2552, '2', '06', '02', '10', '000', 'ALAT KOMUNIKASI SATELIT'),
			(2553, '2', '06', '02', '10', '001', 'FULLY SYSTEM HUB'),
			(2554, '2', '06', '02', '10', '002', 'VSAT SYSTEM FOR REMOTE TERMINAL'),
			(2555, '2', '06', '02', '10', '003', 'COMMOB (COMMUNICATION MOBILE) VSAT'),
			(2556, '2', '06', '02', '10', '004', 'WIRELESS BASE STATION + SURVEILLANCE MANPACK KIT'),
			(2557, '2', '06', '02', '10', '005', 'FLYAWAY'),
			(2558, '2', '06', '02', '10', '006', 'ENCRYPTION'),
			(2559, '2', '06', '02', '10', '007', 'REMOTE DATA CONNECTION DISTRIBUTION'),
			(2560, '2', '06', '02', '10', '008', 'REMOTE VOIP GATEWAY E1 CARD INTERFACE'),
			(2561, '2', '06', '02', '10', '009', 'SPECTRUM ANALYZER FOR HUB STATION'),
			(2562, '2', '06', '02', '10', '010', 'SPECTRUM ANALYZER PORTABLE FOR FIELD USE'),
			(2563, '2', '06', '02', '10', '011', 'UPS 15 KVA FOR HUB STATION'),
			(2564, '2', '06', '02', '10', '012', 'UPS 1 KVA FOR REMOTE STATION'),
			(2565, '2', '06', '02', '10', '999', 'ALAT KOMUNIKASI SATELIT LAINNYA'),
			(2566, '2', '06', '02', '99', '000', 'ALAT KOMUNIKASI LAINNYA'),
			(2567, '2', '06', '02', '99', '999', 'ALAT KOMUNIKASI LAINNYA'),
			(2568, '2', '06', '03', '00', '000', 'PERALATAN PEMANCAR'),
			(2569, '2', '06', '03', '01', '000', 'PERALATAN PEMANCAR MF/MW'),
			(2570, '2', '06', '03', '01', '001', 'UNIT PEMANCAR MF/MW PORTABLE'),
			(2571, '2', '06', '03', '01', '002', 'UNIT PEMANCAR MF/MW TRANSPORTABLE'),
			(2572, '2', '06', '03', '01', '003', 'UNIT PEMANCAR MF/MW STATIONARY'),
			(2573, '2', '06', '03', '01', '999', 'PERALATAN PEMANCAR MF/MW LAINNYA'),
			(2574, '2', '06', '03', '02', '000', 'PERALATAN PEMANCAR HF/SW'),
			(2575, '2', '06', '03', '02', '001', 'UNIT PEMANCAR HF/SW PORTABLE'),
			(2576, '2', '06', '03', '02', '002', 'UNIT PEMANCAR HF/SW TRANSPORTABLE'),
			(2577, '2', '06', '03', '02', '003', 'UNIT PEMANCAR HF/SW STATIONARY'),
			(2578, '2', '06', '03', '02', '999', 'PERALATAN PEMANCAR HF/SW LAINNYA'),
			(2579, '2', '06', '03', '03', '000', 'PERALATAN PEMANCAR VHF/FM'),
			(2580, '2', '06', '03', '03', '001', 'UNIT PEMANCAR VHF/FM PORTABLE'),
			(2581, '2', '06', '03', '03', '002', 'UNIT PEMANCAR VHF/FM TRANSPORTABLE'),
			(2582, '2', '06', '03', '03', '003', 'UNIT PEMANCAR VHF/FM STATIONARY'),
			(2583, '2', '06', '03', '03', '999', 'PERALATAN PEMANCAR VHF/FM LAINNYA'),
			(2584, '2', '06', '03', '04', '000', 'PERALATAN PEMANCAR UHF'),
			(2585, '2', '06', '03', '04', '001', 'UNIT PEMANCAR UHF PORTABLE'),
			(2586, '2', '06', '03', '04', '002', 'UNIT PEMANCAR UHF TRANSPORTABLE'),
			(2587, '2', '06', '03', '04', '003', 'UNIT PEMANCAR UHF STATIONARY'),
			(2588, '2', '06', '03', '04', '004', 'PORTABLE REPORTER LINK'),
			(2589, '2', '06', '03', '04', '999', 'PERALATAN PEMANCAR UHF LAINNYA'),
			(2590, '2', '06', '03', '05', '000', 'PERALATAN PEMANCAR SHF'),
			(2591, '2', '06', '03', '05', '001', 'UNIT PEMANCAR SHF PORTABLE'),
			(2592, '2', '06', '03', '05', '002', 'UNIT PEMANCAR SHF TRANSPORTABLE'),
			(2593, '2', '06', '03', '05', '003', 'UNIT PEMANCAR SHF STATIONARY'),
			(2594, '2', '06', '03', '05', '004', 'SATELLITE LINK ( UP/DOWN LINK )'),
			(2595, '2', '06', '03', '05', '999', 'PERALATAN PEMANCAR SHF LAINNYA'),
			(2596, '2', '06', '03', '06', '000', 'PERALATAN ANTENA MF/MW'),
			(2597, '2', '06', '03', '06', '001', 'ANTENE MF/MW PORTABLE'),
			(2598, '2', '06', '03', '06', '002', 'ANTENE MF/MW TRANSPORTABLE'),
			(2599, '2', '06', '03', '06', '003', 'ANTENE MF/MW STATIONARY'),
			(2600, '2', '06', '03', '06', '999', 'PERALATAN ANTENA MF/MW LAINNYA'),
			(2601, '2', '06', '03', '07', '000', 'PERALATAN ANTENA HF/SW'),
			(2602, '2', '06', '03', '07', '001', 'ANTENE HF/SW PORTABLE'),
			(2603, '2', '06', '03', '07', '002', 'ANTENE HF/SW TRANSPORTABLE'),
			(2604, '2', '06', '03', '07', '003', 'ANTENE HF/SW STATIONARY'),
			(2605, '2', '06', '03', '07', '999', 'PERALATAN ANTENA HF/SW LAINNYA'),
			(2606, '2', '06', '03', '08', '000', 'PERALATAN ANTENA VHF/FM'),
			(2607, '2', '06', '03', '08', '001', 'ANTENE VHF/FM PORTABLE'),
			(2608, '2', '06', '03', '08', '002', 'ANTENE VHF/FM TRANSPORTABLE'),
			(2609, '2', '06', '03', '08', '003', 'ANTENE VHF/FM STATIONARY'),
			(2610, '2', '06', '03', '08', '999', 'PERALATAN ANTENA VHF/FM LAINNYA'),
			(2611, '2', '06', '03', '09', '000', 'PERALATAN ANTENA UHF'),
			(2612, '2', '06', '03', '09', '001', 'ANTENE UHF PORTABLE'),
			(2613, '2', '06', '03', '09', '002', 'ANTENE UHF TRANSPORTABLE'),
			(2614, '2', '06', '03', '09', '003', 'ANTENE UHF STATIONARY'),
			(2615, '2', '06', '03', '09', '999', 'PERALATAN ANTENA UHF LAINNYA'),
			(2616, '2', '06', '03', '10', '000', 'PERALATAN ANTENA SHF/PARABOLA'),
			(2617, '2', '06', '03', '10', '001', 'ANTENE SHF PORTABLE'),
			(2618, '2', '06', '03', '10', '002', 'ANTENE SHF TRANSPORTABLE'),
			(2619, '2', '06', '03', '10', '003', 'ANTENE SHF STATIONARY'),
			(2620, '2', '06', '03', '10', '004', 'ANTENA ALL BAND'),
			(2621, '2', '06', '03', '10', '005', 'ANTENA SSB'),
			(2622, '2', '06', '03', '10', '999', 'PERALATAN ANTENA SHF/PARABOLA LAINNYA'),
			(2623, '2', '06', '03', '11', '000', 'PERALATAN TRANSLATOR VHF/VHF'),
			(2624, '2', '06', '03', '11', '001', 'TRANSLATOR VHF/VHF PORTABLE'),
			(2625, '2', '06', '03', '11', '002', 'TRANSLATOR VHF/VHF TRANSPORTABLE'),
			(2626, '2', '06', '03', '11', '003', 'TRANSLATOR VHF/VHF STATIONARY'),
			(2627, '2', '06', '03', '11', '999', 'PERALATAN TRANSLATOR VHF/VHF LAINNYA'),
			(2628, '2', '06', '03', '12', '000', 'PERALATAN TRANSLATOR UHF/UHF'),
			(2629, '2', '06', '03', '12', '001', 'TRANSLATOR UHF/UHF PORTABLE'),
			(2630, '2', '06', '03', '12', '002', 'TRANSLATOR UHF/UHF TRANSPORTABLE'),
			(2631, '2', '06', '03', '12', '003', 'TRANSLATOR UHF/UHF STATIONARY'),
			(2632, '2', '06', '03', '12', '999', 'PERALATAN TRANSLATOR UHF/UHF LAINNYA'),
			(2633, '2', '06', '03', '13', '000', 'PERALATAN TRANSLATOR VHF/UHF'),
			(2634, '2', '06', '03', '13', '001', 'TRANSLATOR VHF/UHF PORTABLE'),
			(2635, '2', '06', '03', '13', '002', 'TRANSLATOR VHF/UHF TRANSPORTABLE'),
			(2636, '2', '06', '03', '13', '003', 'TRANSLATOR VHF/UHF STATIONARY'),
			(2637, '2', '06', '03', '13', '999', 'PERALATAN TRANSLATOR VHF/UHF LAINNYA'),
			(2638, '2', '06', '03', '14', '000', 'PERALATAN TRANSLATOR UHF/VHF'),
			(2639, '2', '06', '03', '14', '001', 'TRANSLATOR UHF/VHF PORTABLE'),
			(2640, '2', '06', '03', '14', '002', 'TRANSLATOR UHF/VHF TRANSPORTABLE'),
			(2641, '2', '06', '03', '14', '003', 'TRANSLATOR UHF/VHF STATIONARY'),
			(2642, '2', '06', '03', '14', '999', 'PERALATAN TRANSLATOR UHF/VHF LAINNYA'),
			(2643, '2', '06', '03', '15', '000', 'PERALATAN MICROWAVE F P U'),
			(2644, '2', '06', '03', '15', '001', 'MICROWAVE F P U PORTABLE'),
			(2645, '2', '06', '03', '15', '002', 'MICROWAVE F P U TRANSPORTABLE'),
			(2646, '2', '06', '03', '15', '003', 'MICROWAVE F P U STATIONARY'),
			(2647, '2', '06', '03', '15', '999', 'PERALATAN MICROWAVE F P U LAINNYA'),
			(2648, '2', '06', '03', '16', '000', 'PERALATAN MICROWAVE TERESTRIAL'),
			(2649, '2', '06', '03', '16', '001', 'MICROWAVE TERESTRIAL PORTABLE'),
			(2650, '2', '06', '03', '16', '002', 'MICROWAVE TERESTRIAL TRANSPORTABLE'),
			(2651, '2', '06', '03', '16', '003', 'MICROWAVE TERESTRIAL STATIONARY'),
			(2652, '2', '06', '03', '16', '999', 'PERALATAN MICROWAVE TERESTRIAL LAINNYA'),
			(2653, '2', '06', '03', '17', '000', 'PERALATAN MICROWAVE TVRO'),
			(2654, '2', '06', '03', '17', '001', 'MICROWAVE TVRO PORTABLE'),
			(2655, '2', '06', '03', '17', '002', 'MICROWAVE TVRO TRANSPORTABLE'),
			(2656, '2', '06', '03', '17', '003', 'MICROWAVE TVRO STATIONARY'),
			(2657, '2', '06', '03', '17', '999', 'PERALATAN MICROWAVE TVRO LAINNYA'),
			(2658, '2', '06', '03', '18', '000', 'PERALATAN DUMMY LOAD'),
			(2659, '2', '06', '03', '18', '001', 'DUMMY LOAD PENDINGIN UDARA'),
			(2660, '2', '06', '03', '18', '002', 'DUMMY LOAD PENDINGIN AIR'),
			(2661, '2', '06', '03', '18', '003', 'DUMMY LOAD PENDINGIN MINYAK'),
			(2662, '2', '06', '03', '18', '004', 'DUMMY LOAD PENDINGIN GAS'),
			(2663, '2', '06', '03', '18', '999', 'PERALATAN DUMMY LOAD LAINNYA'),
			(2664, '2', '06', '03', '19', '000', 'SWITCHER ANTENA'),
			(2665, '2', '06', '03', '19', '001', 'SWITCHER COMBINATION'),
			(2666, '2', '06', '03', '19', '002', 'SWITCHER MANUAL'),
			(2667, '2', '06', '03', '19', '003', 'SWITCHER AUTOMATIC MOTOR'),
			(2668, '2', '06', '03', '19', '999', 'SWITCHER ANTENA LAINNYA'),
			(2669, '2', '06', '03', '20', '000', 'SWITCHER/MENARA ANTENA'),
			(2670, '2', '06', '03', '20', '001', 'SELF SUPPORTING TOWER'),
			(2671, '2', '06', '03', '20', '002', 'GUY TOWER'),
			(2672, '2', '06', '03', '20', '003', 'MAST TOWER'),
			(2673, '2', '06', '03', '20', '004', 'CONCRETE TOWER'),
			(2674, '2', '06', '03', '20', '999', 'SWITCHER/MENARA ANTENA LAINNYA'),
			(2675, '2', '06', '03', '21', '000', 'FEEDER'),
			(2676, '2', '06', '03', '21', '001', 'OPEN WIRE'),
			(2677, '2', '06', '03', '21', '002', 'COAXIAL FEEDER'),
			(2678, '2', '06', '03', '21', '003', 'ANTENNA TUNING UNIT'),
			(2679, '2', '06', '03', '21', '004', 'DEHYDRATOR'),
			(2680, '2', '06', '03', '21', '999', '\"FEEDER LAINNYA (ALAT STUDIO'),
			(2681, '2', '06', '03', '22', '000', 'HUMIDITY CONTROL'),
			(2682, '2', '06', '03', '22', '001', 'DEHUMIDIFIER (HUMIDITY CONTROL)'),
			(2683, '2', '06', '03', '22', '999', 'HUMIDITY CONTROL LAINNYA'),
			(2684, '2', '06', '03', '23', '000', 'PROGRAM INPUT EQUIPMENT'),
			(2685, '2', '06', '03', '23', '001', 'RECEIVER STL/VHF ( FM)'),
			(2686, '2', '06', '03', '23', '002', 'RECEIVER STL/UHF'),
			(2687, '2', '06', '03', '23', '003', 'RECEIVER STL/SHF'),
			(2688, '2', '06', '03', '23', '004', 'TVRO'),
			(2689, '2', '06', '03', '23', '005', 'LINE AMPLIFIER'),
			(2690, '2', '06', '03', '23', '006', 'S R O'),
			(2691, '2', '06', '03', '23', '007', 'LINE EQUALIZER'),
			(2692, '2', '06', '03', '23', '008', 'AUTOMATIC GAIN CONTROL'),
			(2693, '2', '06', '03', '23', '009', 'COMPRESSOR AMPLIFIER'),
			(2694, '2', '06', '03', '23', '010', 'EXPANDER AMPLIFIER'),
			(2695, '2', '06', '03', '23', '011', 'ATTENUATOR'),
			(2696, '2', '06', '03', '23', '012', 'AUDIO PROCESSOR AM'),
			(2697, '2', '06', '03', '23', '013', 'STEREO GENERATOR FM'),
			(2698, '2', '06', '03', '23', '014', 'DISTRIBUTOR AMPLIFIER'),
			(2699, '2', '06', '03', '23', '015', 'SWITCHER/PATCH PANEL'),
			(2700, '2', '06', '03', '23', '016', 'AUDIO MONITOR'),
			(2701, '2', '06', '03', '23', '017', 'AM MONITOR'),
			(2702, '2', '06', '03', '23', '018', 'FM MONITOR'),
			(2703, '2', '06', '03', '23', '019', 'POWER DISTRIBUTION BOARD'),
			(2704, '2', '06', '03', '23', '020', 'LIGHTNING PROTECTOR'),
			(2705, '2', '06', '03', '23', '021', 'ALL BAND RECEIVER'),
			(2706, '2', '06', '03', '23', '022', 'CHANGE OVER SWITCH'),
			(2707, '2', '06', '03', '23', '999', 'PROGRAM INPUT EQUIPMENT LAINNYA'),
			(2708, '2', '06', '03', '24', '000', 'PERALATAN ANTENE PENERIMA VHF'),
			(2709, '2', '06', '03', '24', '001', 'ANTENE PENERIMA VHF'),
			(2710, '2', '06', '03', '24', '002', 'PERALATAN ANTENA PENERIMA LF'),
			(2711, '2', '06', '03', '24', '003', 'PERALATAN ANTENA PENERIMA MF'),
			(2712, '2', '06', '03', '24', '004', 'PERALATAN ANTENA PENERIMA HF'),
			(2713, '2', '06', '03', '24', '005', 'PERALATAN ANTENA PENERIMA MF+HF'),
			(2714, '2', '06', '03', '24', '006', 'PERALATAN ANTENA PENERIMA VHF'),
			(2715, '2', '06', '03', '24', '007', 'PERALATAN ANTENA PENERIMA UHF'),
			(2716, '2', '06', '03', '24', '008', 'PERALATAN ANTENA PENERIMA SSHF'),
			(2717, '2', '06', '03', '24', '999', 'PERALATAN ANTENE PENERIMA VHF LAINNYA'),
			(2718, '2', '06', '03', '25', '000', 'PERALATAN PEMANCAR LF'),
			(2719, '2', '06', '03', '25', '001', 'PERALATAN PEMANCAR LF TRANSPORTABLE'),
			(2720, '2', '06', '03', '25', '002', 'PERALATAN PEMANCAR LF PORTABLE'),
			(2721, '2', '06', '03', '25', '003', 'PERALATAN PEMANCAR LF STATIONARY'),
			(2722, '2', '06', '03', '25', '999', 'PERALATAN PEMANCAR LF LAINNYA'),
			(2723, '2', '06', '03', '26', '000', 'UNIT PEMANCAR MF+HF'),
			(2724, '2', '06', '03', '26', '001', 'UNIT PEMANCAR MF+HF TRANSPORTABLE'),
			(2725, '2', '06', '03', '26', '002', 'UNIT PEMANCAR MF+HF PORTABLE'),
			(2726, '2', '06', '03', '26', '003', 'UNIT PEMANCAR MF+HF STATIONARY'),
			(2727, '2', '06', '03', '26', '999', 'UNIT PEMANCAR MF+HF LAINNYA'),
			(2728, '2', '06', '03', '27', '000', 'PERALATAN ANTENA PEMANCAR MF+HF'),
			(2729, '2', '06', '03', '27', '001', 'PERALATAN ANTENA PEMANCAR MF+HF TRANSPORTABLE'),
			(2730, '2', '06', '03', '27', '002', 'PERALATAN ANTENA PEMANCAR MF+HF PORTABLE'),
			(2731, '2', '06', '03', '27', '003', 'PERALATAN ANTENA PEMANCAR MF+HF STATIONARY'),
			(2732, '2', '06', '03', '27', '999', 'PERALATAN ANTENA PEMANCAR MF+HF LAINNYA'),
			(2733, '2', '06', '03', '28', '000', 'PERALATAN PENERIMA'),
			(2734, '2', '06', '03', '28', '001', 'PERALATAN PENERIMA LF'),
			(2735, '2', '06', '03', '28', '002', 'PERALATAN PENERIMA MF'),
			(2736, '2', '06', '03', '28', '003', 'PERALATAN PENERIMA HF'),
			(2737, '2', '06', '03', '28', '004', 'PERALATAN PENERIMA MF+HF'),
			(2738, '2', '06', '03', '28', '005', 'PERALATAN PENERIMA UHF'),
			(2739, '2', '06', '03', '28', '006', 'PERALATAN PENERIMA SHF'),
			(2740, '2', '06', '03', '28', '999', 'PERALATAN PENERIMA LAINNYA'),
			(2741, '2', '06', '03', '29', '000', 'PERALATAN PEMANCAR DAN PENERIMA LF'),
			(2742, '2', '06', '03', '29', '001', 'UNIT TRANSCEIVER LF TRANSPORTABLE'),
			(2743, '2', '06', '03', '29', '002', 'UNIT TRANSCEIVER LF PORTABLE'),
			(2744, '2', '06', '03', '29', '003', 'UNIT TRANSCEIVER LF STATIONARY'),
			(2745, '2', '06', '03', '29', '999', 'PERALATAN PEMANCAR DAN PENERIMA LF LAINNYA'),
			(2746, '2', '06', '03', '30', '000', 'PERALATAN PEMANCAR DAN PENERIMA MF'),
			(2747, '2', '06', '03', '30', '001', 'UNIT TRANSCEIVER MF TRANSPORTABLE'),
			(2748, '2', '06', '03', '30', '002', 'UNIT TRANSCEIVER MF PORTABLE'),
			(2749, '2', '06', '03', '30', '003', 'UNIT TRANSCEIVER MF STATIONARY'),
			(2750, '2', '06', '03', '30', '999', 'PERALATAN PEMANCAR DAN PENERIMA MF LAINNYA'),
			(2751, '2', '06', '03', '31', '000', 'PERALATAN PEMANCAR DAN PENERIMA HF'),
			(2752, '2', '06', '03', '31', '001', 'UNIT TRANSCEIVER HF TRANSPORTABLE'),
			(2753, '2', '06', '03', '31', '002', 'UNIT TRANSCEIVER HF PORTABLE'),
			(2754, '2', '06', '03', '31', '003', 'UNIT TRANSCEIVER HF STATIONARY'),
			(2755, '2', '06', '03', '31', '004', 'RS SSB TRANCIEVER'),
			(2756, '2', '06', '03', '31', '005', 'MINI RANGER'),
			(2757, '2', '06', '03', '31', '006', 'ARTEMIS'),
			(2758, '2', '06', '03', '31', '007', 'TELEROMETER'),
			(2759, '2', '06', '03', '31', '999', 'PERALATAN PEMANCAR DAN PENERIMA HF LAINNYA'),
			(2760, '2', '06', '03', '32', '000', 'PERALATAN PEMANCAR DAN PENERIMA MF+HF'),
			(2761, '2', '06', '03', '32', '001', 'UNIT TRANSCEIVER MF+HF TRANSPORTABLE'),
			(2762, '2', '06', '03', '32', '002', 'UNIT TRANSCEIVER MF+HF PORTABLE'),
			(2763, '2', '06', '03', '32', '003', 'UNIT TRANSCEIVER MF+HF STATIONARY'),
			(2764, '2', '06', '03', '32', '004', 'DIFFERENTIAL OMEGA (PERALATAN PEMANCAR DAN PENERIMA MF+HF)'),
			(2765, '2', '06', '03', '32', '999', 'PERALATAN PEMANCAR DAN PENERIMA MF+HF LAINNYA'),
			(2766, '2', '06', '03', '33', '000', 'PERALATAN PEMANCAR DAN PENERIMA VHF'),
			(2767, '2', '06', '03', '33', '001', 'UNIT TRANSCEIVER VHF TRANSPORTABLE'),
			(2768, '2', '06', '03', '33', '002', 'UNIT TRANSCEIVER VHF PORTABLE'),
			(2769, '2', '06', '03', '33', '003', 'UNIT TRANSCEIVER VHF STATIONARY'),
			(2770, '2', '06', '03', '33', '999', 'PERALATAN PEMANCAR DAN PENERIMA VHF LAINNYA'),
			(2771, '2', '06', '03', '34', '000', 'PERALATAN PEMANCAR DAN PENERIMA UHF'),
			(2772, '2', '06', '03', '34', '001', 'UNIT TRANSCEIVER UHF TRANSPORTABLE'),
			(2773, '2', '06', '03', '34', '002', 'UNIT TRANSCEIVER UHF PORTABLE'),
			(2774, '2', '06', '03', '34', '003', 'UNIT TRANSCEIVER UHF STATIONARY'),
			(2775, '2', '06', '03', '34', '004', 'ULTRA HIGHT FREQUENCE LINK'),
			(2776, '2', '06', '03', '34', '005', 'AUTO ALARM TUSTEL (AAT)'),
			(2777, '2', '06', '03', '34', '006', 'DISTRIBUTION BOARD AND SIGNAL UNIT'),
			(2778, '2', '06', '03', '34', '007', 'REMOTE TERMINAL UNIT'),
			(2779, '2', '06', '03', '34', '008', 'MULTIPLEX TERMINAL EQUIPMENT'),
			(2780, '2', '06', '03', '34', '009', 'SIGNAL VELVOGER GROUNDING'),
			(2781, '2', '06', '03', '34', '010', 'BRIDGE MERGER TESTING'),
			(2782, '2', '06', '03', '34', '011', 'MESSAGE REPEATER'),
			(2783, '2', '06', '03', '34', '012', 'ELECTRIC CLEANER'),
			(2784, '2', '06', '03', '34', '013', 'AOTOMATIC AERLALE'),
			(2785, '2', '06', '03', '34', '014', 'POWER AND AWR METER ROUND'),
			(2786, '2', '06', '03', '34', '015', 'VOLTAGE REGULATOR'),
			(2787, '2', '06', '03', '34', '016', 'GYRO COMPASS'),
			(2788, '2', '06', '03', '34', '017', 'FREQUENCE SYNTHESIZER UNIT'),
			(2789, '2', '06', '03', '34', '018', 'VODAS (VOICE DEVISE ANTI SINGING)'),
			(2790, '2', '06', '03', '34', '019', 'ANEMOMETER (PERALATAN PEMANCAR DAN PENERIMA UHF)'),
			(2791, '2', '06', '03', '34', '020', 'CLEAR VIEW SCREEN'),
			(2792, '2', '06', '03', '34', '021', 'ARQ UNIT'),
			(2793, '2', '06', '03', '34', '022', 'RADIO DIRECTION FINDER'),
			(2794, '2', '06', '03', '34', '023', 'POWER TRANSMITTER'),
			(2795, '2', '06', '03', '34', '024', 'TELE CONTROLLER'),
			(2796, '2', '06', '03', '34', '025', 'LOCAL TERMINAL'),
			(2797, '2', '06', '03', '34', '026', 'DIGITAL SELECTIVE CALLING (DSC)'),
			(2798, '2', '06', '03', '34', '999', 'PERALATAN PEMANCAR DAN PENERIMA UHF LAINNYA'),
			(2799, '2', '06', '03', '35', '000', 'PERALATAN PEMANCAR DAN PENERIMA SHF'),
			(2800, '2', '06', '03', '35', '001', 'UNIT TRANSCEIVER SHF TRANSPORTABLE'),
			(2801, '2', '06', '03', '35', '002', 'UNIT TRANSCEIVER SHF PORTABLE'),
			(2802, '2', '06', '03', '35', '003', 'UNIT TRANSCEIVER SHF STATIONARY'),
			(2803, '2', '06', '03', '35', '999', 'PERALATAN PEMANCAR DAN PENERIMA SHF LAINNYA'),
			(2804, '2', '06', '03', '36', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA LF'),
			(2805, '2', '06', '03', '36', '001', 'UNIT ANTENA TRANSCEIVER LF TRANSPORTABLE'),
			(2806, '2', '06', '03', '36', '002', 'UNIT ANTENA TRANSCEIVER LF PORTABLE'),
			(2807, '2', '06', '03', '36', '003', 'UNIT ANTENA TRANSCEIVER LF STATIONARY'),
			(2808, '2', '06', '03', '36', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA LF LAINNYA'),
			(2809, '2', '06', '03', '37', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA MF'),
			(2810, '2', '06', '03', '37', '001', 'UNIT ANTENA TRANSCEIVER MF TRANSPORTABLE'),
			(2811, '2', '06', '03', '37', '002', 'UNIT ANTENA TRANSCEIVER MF PORTABLE'),
			(2812, '2', '06', '03', '37', '003', 'UNIT ANTENA TRANSCEIVER MF STATIONARY'),
			(2813, '2', '06', '03', '37', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA MF LAINNYA'),
			(2814, '2', '06', '03', '38', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA HF'),
			(2815, '2', '06', '03', '38', '001', 'UNIT ANTENA TRANSCEIVER HF TRANSPORTABLE'),
			(2816, '2', '06', '03', '38', '002', 'UNIT ANTENA TRANSCEIVER HF PORTABLE'),
			(2817, '2', '06', '03', '38', '003', 'UNIT ANTENA TRANSCEIVER HF STATIONARY'),
			(2818, '2', '06', '03', '38', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA HF LAINNYA'),
			(2819, '2', '06', '03', '39', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA MF+HF'),
			(2820, '2', '06', '03', '39', '001', 'UNIT ANTENA TRANSCEIVER MF+ HF TRANSPORTABLE'),
			(2821, '2', '06', '03', '39', '002', 'UNIT ANTENA TRANSCEIVER MF+HF PORTABLE'),
			(2822, '2', '06', '03', '39', '003', 'UNIT ANTENA TRANSCEIVER MF+HF STATIONARY'),
			(2823, '2', '06', '03', '39', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA MF+HF LAINNYA'),
			(2824, '2', '06', '03', '40', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA VHF'),
			(2825, '2', '06', '03', '40', '001', 'UNIT ANTENA TRANSCEIVER VHF TRANSPORTABLE'),
			(2826, '2', '06', '03', '40', '002', 'UNIT ANTENA TRANSCEIVER VHF PORTABLE'),
			(2827, '2', '06', '03', '40', '003', 'UNIT ANTENA TRANSCEIVER VHF STATIONARY'),
			(2828, '2', '06', '03', '40', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA VHF LAINNYA'),
			(2829, '2', '06', '03', '41', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA UHF'),
			(2830, '2', '06', '03', '41', '001', 'UNIT ANTENA TRANSCEIVER UHF TRANSPORTABLE'),
			(2831, '2', '06', '03', '41', '002', 'UNIT ANTENA TRANSCEIVER UHF PORTABLE'),
			(2832, '2', '06', '03', '41', '003', 'UNIT ANTENA TRANSCEIVER UHF STATIONARY'),
			(2833, '2', '06', '03', '41', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA UHF LAINNYA'),
			(2834, '2', '06', '03', '42', '000', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA SHF'),
			(2835, '2', '06', '03', '42', '001', 'UNIT ANTENA TRANSCEIVER SHF TRANSPORTABLE'),
			(2836, '2', '06', '03', '42', '002', 'UNIT ANTENA TRANSCEIVER SHF PORTABLE'),
			(2837, '2', '06', '03', '42', '003', 'UNIT ANTENA TRANSCEIVER SHF STATIONARY'),
			(2838, '2', '06', '03', '42', '999', 'PERALATAN ANTENA PEMANCAR DAN PENERIMA SHF LAINNYA'),
			(2839, '2', '06', '03', '43', '000', 'PERALATAN PENERIMA CUACA CITRA SATELITE RESOLUSI RENDAH'),
			(2840, '2', '06', '03', '43', '001', 'ALAT PENERIMA SATELITE CUACA'),
			(2841, '2', '06', '03', '43', '999', 'PERALATAN PENERIMA CUACA CITRA SATELITE RESOLUSI RENDAH LAINNYA'),
			(2842, '2', '06', '03', '44', '000', 'PERALATAN PENERIMA CUACA CITRA SATELITE RESOLUSI TINGGI'),
			(2843, '2', '06', '03', '44', '001', 'ALAT PENERIMA SATELITE CUACA GEO STASIMETER'),
			(2844, '2', '06', '03', '44', '002', 'ALAT PENERIMA SATELITE CUACA ORBIT POLAR'),
			(2845, '2', '06', '03', '44', '999', 'PERALATAN PENERIMA CUACA CITRA SATELITE RESOLUSI TINGGI LAINNYA'),
			(2846, '2', '06', '03', '45', '000', 'PERALATAN PENERIMA DAN PENGIRIM GAMBAR KE PERMUKAAN'),
			(2847, '2', '06', '03', '45', '001', 'SCANNER FACSIMILE'),
			(2848, '2', '06', '03', '45', '002', 'ALDEN MINIFAX RECORDER'),
			(2849, '2', '06', '03', '45', '003', 'UNIVERSAL GRAPHIC RECORDER'),
			(2850, '2', '06', '03', '45', '004', 'WEATHER CHART RECORDER'),
			(2851, '2', '06', '03', '45', '999', 'PERALATAN PENERIMA DAN PENGIRIM GAMBAR KE PERMUKAAN LAINNYA'),
			(2852, '2', '06', '03', '46', '000', 'PERALATAN PERLENGKAPAN RADIO'),
			(2853, '2', '06', '03', '46', '001', 'BOX BATTERY'),
			(2854, '2', '06', '03', '46', '002', 'CUTTON DUCK'),
			(2855, '2', '06', '03', '46', '003', 'CARRING CASE'),
			(2856, '2', '06', '03', '46', '004', 'HAND SET'),
			(2857, '2', '06', '03', '46', '005', 'CONECCTOR'),
			(2858, '2', '06', '03', '46', '999', 'PERALATAN PERLENGKAPAN RADIO LAINNYA'),
			(2859, '2', '06', '03', '47', '000', 'SUMBER TENAGA'),
			(2860, '2', '06', '03', '47', '001', 'BA-30'),
			(2861, '2', '06', '03', '47', '002', 'GENSET'),
			(2862, '2', '06', '03', '47', '003', 'SOLAR CELL'),
			(2863, '2', '06', '03', '47', '004', 'CHARGER'),
			(2864, '2', '06', '03', '47', '999', 'SUMBER TENAGA LAINNYA'),
			(2865, '2', '06', '03', '99', '000', 'PERALATAN PEMANCAR LAINNYA'),
			(2866, '2', '06', '03', '99', '999', 'PERALATAN PEMANCAR LAINNYA'),
			(2867, '2', '06', '04', '00', '000', 'PERALATAN KOMUNIKASI NAVIGASI'),
			(2868, '2', '06', '04', '01', '000', 'PERALATAN KOMUNIKASI NAVIGASI INSTRUMEN LANDING SYSTEM'),
			(2869, '2', '06', '04', '01', '001', 'STANDARD INSTRUMEN LANDING SYSTEM'),
			(2870, '2', '06', '04', '01', '002', 'MICROWAVE LANDING SYSTEM'),
			(2871, '2', '06', '04', '01', '999', 'PERALATAN KOMUNIKASI NAVIGASI INSTRUMEN LANDING SYSTEM LAINNYA'),
			(2872, '2', '06', '04', '02', '000', 'VERY HIGHT FREQUENCE OMNI RANGE (VOR)'),
			(2873, '2', '06', '04', '02', '001', 'CONVENTIONAL VOR (CVOR)'),
			(2874, '2', '06', '04', '02', '002', 'DOOPLE VOR (DVOR)'),
			(2875, '2', '06', '04', '02', '999', 'VERY HIGHT FREQUENCE OMNI RANGE (VOR) LAINNYA'),
			(2876, '2', '06', '04', '03', '000', 'DISTANCE MEASURING EQUIPMENT (DME)'),
			(2877, '2', '06', '04', '03', '001', 'TRANSPONDER DME'),
			(2878, '2', '06', '04', '03', '002', 'ANTENA DME'),
			(2879, '2', '06', '04', '03', '003', 'BEACON'),
			(2880, '2', '06', '04', '03', '004', 'NDB'),
			(2881, '2', '06', '04', '03', '005', 'DB'),
			(2882, '2', '06', '04', '03', '006', 'RADAR BEACON'),
			(2883, '2', '06', '04', '03', '007', 'DIFFERENTIAL OMEGA (DISTANCE MEASURING EQUIPMENT (DME))'),
			(2884, '2', '06', '04', '03', '008', 'DIFFERENTIAL GPS'),
			(2885, '2', '06', '04', '03', '999', 'DISTANCE MEASURING EQUIPMENT (DME) LAINNYA'),
			(2886, '2', '06', '04', '04', '000', 'RADAR'),
			(2887, '2', '06', '04', '04', '001', 'PRIMARY SURVEILLANCE RADAR'),
			(2888, '2', '06', '04', '04', '002', 'SECONDARY SURVEILLANCE RADAR'),
			(2889, '2', '06', '04', '04', '999', 'RADAR LAINNYA'),
			(2890, '2', '06', '04', '05', '000', 'ALAT PENGATUR TELEKOMUNIKASI'),
			(2891, '2', '06', '04', '05', '001', 'MESSAGE SWITCHING CENTER (MSC)'),
			(2892, '2', '06', '04', '05', '002', 'AUTOMATIC MESSAGE SWITCHING CENTER (AMSC)'),
			(2893, '2', '06', '04', '05', '003', 'CURRENT CONSOLE REGULATOR'),
			(2894, '2', '06', '04', '05', '004', 'CONTROLLER CONSOLE PVC'),
			(2895, '2', '06', '04', '05', '006', 'NO BREAK CASINET'),
			(2896, '2', '06', '04', '05', '007', 'TELEGRAPHIC FRAME'),
			(2897, '2', '06', '04', '05', '008', 'MORDEN'),
			(2898, '2', '06', '04', '05', '009', 'RADIO CONSOLE'),
			(2899, '2', '06', '04', '05', '010', 'SUPERVISORI CONSOLE'),
			(2900, '2', '06', '04', '05', '999', 'ALAT PENGATUR TELEKOMUNIKASI LAINNYA'),
			(2901, '2', '06', '04', '06', '000', 'PERALATAN KOMUNIKASI UNTUK DOKUMENTASI'),
			(2902, '2', '06', '04', '06', '001', 'UNIT TAPE RECORDER'),
			(2903, '2', '06', '04', '06', '002', 'UNIT TIME ANNOUNCING'),
			(2904, '2', '06', '04', '06', '003', 'UNIT MASTER CLOCK'),
			(2905, '2', '06', '04', '06', '004', 'UNIT REPRODUCER'),
			(2906, '2', '06', '04', '06', '005', 'UNIT REMOTE CONTROL'),
			(2907, '2', '06', '04', '06', '999', 'PERALATAN KOMUNIKASI UNTUK DOKUMENTASI LAINNYA'),
			(2908, '2', '06', '04', '99', '000', 'PERALATAN KOMUNIKASI NAVIGASI LAINNYA'),
			(2909, '2', '06', '04', '99', '999', 'PERALATAN KOMUNIKASI NAVIGASI LAINNYA'),
			(2910, '2', '07', '00', '00', '000', 'KOMPUTER'),
			(2911, '2', '07', '01', '00', '000', 'KOMPUTER UNIT'),
			(2912, '2', '07', '01', '01', '000', 'KOMPUTER JARINGAN'),
			(2913, '2', '07', '01', '01', '001', 'MAINFRAME (KOMPUTER JARINGAN)'),
			(2914, '2', '07', '01', '01', '002', 'MINI KOMPUTER'),
			(2915, '2', '07', '01', '01', '003', 'LOCAL AREA NETWORK (LAN)'),
			(2916, '2', '07', '01', '01', '004', 'INTERNET'),
			(2917, '2', '07', '01', '01', '005', 'KOMPUTER WEDIS'),
			(2918, '2', '07', '01', '01', '006', 'KOMPUTER SYNERGIE'),
			(2919, '2', '07', '01', '01', '007', 'PC WORKSTATION'),
			(2920, '2', '07', '01', '01', '999', 'KOMPUTER JARINGAN LAINNYA'),
			(2921, '2', '07', '01', '02', '000', 'PERSONAL KOMPUTER'),
			(2922, '2', '07', '01', '02', '001', 'P.C UNIT'),
			(2923, '2', '07', '01', '02', '002', 'LAP TOP'),
			(2924, '2', '07', '01', '02', '003', 'NOTE BOOK'),
			(2925, '2', '07', '01', '02', '004', 'PALM TOP'),
			(2926, '2', '07', '01', '02', '005', 'CODE BREAKER SUPER KOMPUTER'),
			(2927, '2', '07', '01', '02', '006', 'THINCLIENT'),
			(2928, '2', '07', '01', '02', '007', 'NET BOOK'),
			(2929, '2', '07', '01', '02', '008', 'ULTRA MOBILE P.C.'),
			(2930, '2', '07', '01', '02', '999', 'PERSONAL KOMPUTER LAINNYA'),
			(2931, '2', '07', '01', '99', '000', 'KOMPUTER UNIT LAINNYA'),
			(2932, '2', '07', '01', '99', '999', 'KOMPUTER UNIT LAINNYA'),
			(2933, '2', '07', '02', '00', '000', 'PERALATAN KOMPUTER'),
			(2934, '2', '07', '02', '01', '000', 'PERALATAN MAINFRAME'),
			(2935, '2', '07', '02', '01', '001', 'CARD READER (PERALATAN MAINFRAME)'),
			(2936, '2', '07', '02', '01', '002', 'MAGNETIC TAPE UNIT (PERALATAN MAINFRAME)'),
			(2937, '2', '07', '02', '01', '003', 'FLOPPY DISK UNIT (PERALATAN MAINFRAME)'),
			(2938, '2', '07', '02', '01', '004', 'STORAGE MODUL DISK (PERALATAN MAINFRAME)'),
			(2939, '2', '07', '02', '01', '005', 'CONSOLE UNIT (PERALATAN MAINFRAME)'),
			(2940, '2', '07', '02', '01', '006', 'CPU (PERALATAN MAINFRAME)'),
			(2941, '2', '07', '02', '01', '007', 'DISK PACK (PERALATAN MAINFRAME)'),
			(2942, '2', '07', '02', '01', '008', 'HARD COPY CONSOLE'),
			(2943, '2', '07', '02', '01', '009', 'SERIAL PRINTER'),
			(2944, '2', '07', '02', '01', '010', 'LINE PRINTER'),
			(2945, '2', '07', '02', '01', '011', 'PLOTTER (PERALATAN MAINFRAME)'),
			(2946, '2', '07', '02', '01', '012', 'HARD DISK'),
			(2947, '2', '07', '02', '01', '013', 'KEYBOARD (PERALATAN MAINFRAME)'),
			(2948, '2', '07', '02', '01', '014', 'STEAMER'),
			(2949, '2', '07', '02', '01', '015', 'DATA PATCH PANEL'),
			(2950, '2', '07', '02', '01', '016', 'PAPER TAPE READER'),
			(2951, '2', '07', '02', '01', '017', 'PANABOARD'),
			(2952, '2', '07', '02', '01', '999', 'PERALATAN MAINFRAME LAINNYA'),
			(2953, '2', '07', '02', '02', '000', 'PERALATAN MINI KOMPUTER'),
			(2954, '2', '07', '02', '02', '001', 'CARD READER (PERALATAN MINI KOMPUTER)'),
			(2955, '2', '07', '02', '02', '002', 'MAGNETIC TAPE UNIT (PERALATAN MINI KOMPUTER)'),
			(2956, '2', '07', '02', '02', '003', 'FLOPPY DISK UNIT (PERALATAN MINI KOMPUTER)'),
			(2957, '2', '07', '02', '02', '004', 'STORAGE MODUL DISK (PERALATAN MINI KOMPUTER)'),
			(2958, '2', '07', '02', '02', '005', 'CONSOLE UNIT (PERALATAN MINI KOMPUTER)'),
			(2959, '2', '07', '02', '02', '006', 'CPU (PERALATAN MINI KOMPUTER)'),
			(2960, '2', '07', '02', '02', '007', 'DISK PACK (PERALATAN MINI KOMPUTER)'),
			(2961, '2', '07', '02', '02', '009', 'PLOTTER (PERALATAN MINI KOMPUTER)'),
			(2962, '2', '07', '02', '02', '010', 'SCANNER (PERALATAN MINI KOMPUTER)'),
			(2963, '2', '07', '02', '02', '011', 'COMPUTER COMPATIBLE'),
			(2964, '2', '07', '02', '02', '012', 'VIEWER (PERALATAN MINI KOMPUTER)'),
			(2965, '2', '07', '02', '02', '013', 'DIGITIZER (PERALATAN MINI KOMPUTER)'),
			(2966, '2', '07', '02', '02', '014', 'KEYBOARD (PERALATAN MINI KOMPUTER)'),
			(2967, '2', '07', '02', '02', '015', 'AUTO SWITCH/DATA SWITCH'),
			(2968, '2', '07', '02', '02', '016', 'CUT SHEET FEEDER'),
			(2969, '2', '07', '02', '02', '017', 'SPEAKER KOMPUTER'),
			(2970, '2', '07', '02', '02', '999', 'PERALATAN MINI KOMPUTER LAINNYA'),
			(2971, '2', '07', '02', '03', '000', 'PERALATAN PERSONAL KOMPUTER'),
			(2972, '2', '07', '02', '03', '001', 'CPU (PERALATAN PERSONAL KOMPUTER)'),
			(2973, '2', '07', '02', '03', '002', 'MONITOR'),
			(2974, '2', '07', '02', '03', '003', 'PRINTER (PERALATAN PERSONAL KOMPUTER)'),
			(2975, '2', '07', '02', '03', '004', 'SCANNER (PERALATAN PERSONAL KOMPUTER)'),
			(2976, '2', '07', '02', '03', '005', 'PLOTTER (PERALATAN PERSONAL KOMPUTER)'),
			(2977, '2', '07', '02', '03', '006', 'VIEWER (PERALATAN PERSONAL KOMPUTER)'),
			(2978, '2', '07', '02', '03', '007', 'EXTERNAL'),
			(2979, '2', '07', '02', '03', '008', 'DIGITIZER (PERALATAN PERSONAL KOMPUTER)'),
			(2980, '2', '07', '02', '03', '009', 'KEYBOARD (PERALATAN PERSONAL KOMPUTER)'),
			(2981, '2', '07', '02', '03', '010', 'CD WRITTER'),
			(2982, '2', '07', '02', '03', '011', 'DVD WRITER'),
			(2983, '2', '07', '02', '03', '012', 'FIREWIRE CARD'),
			(2984, '2', '07', '02', '03', '013', 'CAPTURE CARD'),
			(2985, '2', '07', '02', '03', '014', 'LAN CARD'),
			(2986, '2', '07', '02', '03', '015', 'EXTERNAL CD/ DVD DRIVE (ROM)'),
			(2987, '2', '07', '02', '03', '016', 'EXTERNAL FLOPPY DISK DRIVE'),
			(2988, '2', '07', '02', '03', '017', 'EXTERNAL/ PORTABLE HARDISK'),
			(2989, '2', '07', '02', '03', '999', 'PERALATAN PERSONAL KOMPUTER LAINNYA'),
			(2990, '2', '07', '02', '04', '000', 'PERALATAN JARINGAN'),
			(2991, '2', '07', '02', '04', '001', 'SERVER'),
			(2992, '2', '07', '02', '04', '002', 'ROUTER'),
			(2993, '2', '07', '02', '04', '003', 'HUB'),
			(2994, '2', '07', '02', '04', '004', 'MODEM'),
			(2995, '2', '07', '02', '04', '005', 'NETWARE INTERFACE EXTERNAL'),
			(2996, '2', '07', '02', '04', '006', 'REPEATER AND TRANSCIEVER'),
			(2997, '2', '07', '02', '04', '007', 'HEAD COPY TERMINAL'),
			(2998, '2', '07', '02', '04', '008', 'RACK MODEM'),
			(2999, '2', '07', '02', '04', '009', 'CARD PUNCH'),
			(3000, '2', '07', '02', '04', '010', 'HEAD COPY PRINTER'),
			(3001, '2', '07', '02', '04', '011', 'CHARACTER TERMINAL'),
			(3002, '2', '07', '02', '04', '012', 'GRAPHIC TERMINAL'),
			(3003, '2', '07', '02', '04', '013', 'TERMINAL'),
			(3004, '2', '07', '02', '04', '014', 'RAK SERVER'),
			(3005, '2', '07', '02', '04', '015', 'FIREWALL'),
			(3006, '2', '07', '02', '04', '016', 'SWITCH RAK'),
			(3007, '2', '07', '02', '04', '017', 'WANSCALLER'),
			(3008, '2', '07', '02', '04', '018', 'E-MAIL SECURITY'),
			(3009, '2', '07', '02', '04', '019', 'CLIENT CLEARING HOUSE'),
			(3010, '2', '07', '02', '04', '020', 'CAT 6 CABLE'),
			(3011, '2', '07', '02', '04', '021', 'KABEL UTP'),
			(3012, '2', '07', '02', '04', '022', 'WIRELESS PCI CARD'),
			(3013, '2', '07', '02', '04', '023', 'WIRELESS ACCESS POINT'),
			(3014, '2', '07', '02', '04', '024', 'SWITCH'),
			(3015, '2', '07', '02', '04', '025', 'HUBBEL UTP'),
			(3016, '2', '07', '02', '04', '026', 'ACCES POINT'),
			(3017, '2', '07', '02', '04', '027', 'RACKMOUNT'),
			(3018, '2', '07', '02', '04', '028', 'KVM KEYBOARD VIDEO MONITOR'),
			(3019, '2', '07', '02', '04', '029', 'MOBILE MODEM GSM/ CDMA'),
			(3020, '2', '07', '02', '04', '030', 'NETWORK CABLE TESTER'),
			(3021, '2', '07', '02', '04', '031', 'JARINGAN SATPAS'),
			(3022, '2', '07', '02', '04', '999', 'PERALATAN JARINGAN LAINNYA'),
			(3023, '2', '07', '02', '99', '000', 'PERALATAN KOMPUTER LAINNYA'),
			(3024, '2', '07', '02', '99', '999', 'PERALATAN KOMPUTER LAINNYA'),
			(3025, '2', '08', '00', '00', '000', 'ALAT PENGEBORAN'),
			(3026, '2', '08', '01', '00', '000', 'ALAT PENGEBORAN MESIN'),
			(3027, '2', '08', '01', '01', '000', 'BOR MESIN TUMBUK'),
			(3028, '2', '08', '01', '01', '001', 'BOR MESIN TUMBUK PAKAI KABEL'),
			(3029, '2', '08', '01', '01', '002', 'BOR MESIN TUMBUK PAKAI SETANG BOR'),
			(3030, '2', '08', '01', '01', '003', 'BOR MESIN TUMBUK KOMBINASI 01 & 02'),
			(3031, '2', '08', '01', '01', '999', 'BOR MESIN TUMBUK LAINNYA'),
			(3032, '2', '08', '01', '02', '000', 'BOR MESIN PUTAR'),
			(3033, '2', '08', '01', '02', '001', 'ROTARY TABLE (BOR MESIN PUTAR)'),
			(3034, '2', '08', '01', '02', '002', 'SPINDLE'),
			(3035, '2', '08', '01', '02', '003', 'KOMBINASI 01 & 02'),
			(3036, '2', '08', '01', '02', '004', 'TOP DRIVE'),
			(3037, '2', '08', '01', '02', '005', 'WIKIE DRILL'),
			(3038, '2', '08', '01', '02', '999', 'BOR MESIN PUTAR LAINNYA'),
			(3039, '2', '08', '01', '99', '000', 'ALAT PENGEBORAN MESIN LAINNYA'),
			(3040, '2', '08', '01', '99', '999', 'ALAT PENGEBORAN MESIN LAINNYA'),
			(3041, '2', '08', '02', '00', '000', 'ALAT PENGEBORAN NON MESIN'),
			(3042, '2', '08', '02', '01', '000', 'BANGKA'),
			(3043, '2', '08', '02', '01', '001', 'BANGKA'),
			(3044, '2', '08', '02', '01', '999', 'BANGKA LAINNYA'),
			(3045, '2', '08', '02', '02', '000', 'PANTEK'),
			(3046, '2', '08', '02', '02', '001', 'PANTEK'),
			(3047, '2', '08', '02', '02', '002', 'SONDIR'),
			(3048, '2', '08', '02', '02', '999', 'PANTEK LAINNYA'),
			(3049, '2', '08', '02', '03', '000', 'PUTAR'),
			(3050, '2', '08', '02', '03', '001', 'PUTAR'),
			(3051, '2', '08', '02', '03', '002', 'BAND HIDROLIK'),
			(3052, '2', '08', '02', '03', '999', 'PUTAR LAINNYA'),
			(3053, '2', '08', '02', '04', '000', 'PERALATAN BANTU'),
			(3054, '2', '08', '02', '04', '001', 'DRAWWORK'),
			(3055, '2', '08', '02', '04', '002', 'DRILL PIPE'),
			(3056, '2', '08', '02', '04', '003', 'DRILL CILLAR'),
			(3057, '2', '08', '02', '04', '004', 'KELLY'),
			(3058, '2', '08', '02', '04', '005', 'CEMETING UNIT'),
			(3059, '2', '08', '02', '04', '006', 'ROTARY TABLE (PERALATAN BANTU)'),
			(3060, '2', '08', '02', '04', '007', 'TUBING SLIP'),
			(3061, '2', '08', '02', '04', '008', 'TUBING SPINDER'),
			(3062, '2', '08', '02', '04', '009', 'ALAT PANCING'),
			(3063, '2', '08', '02', '04', '010', 'SWIVEL'),
			(3064, '2', '08', '02', '04', '011', 'MUD TANK'),
			(3065, '2', '08', '02', '04', '999', 'PERALATAN BANTU LAINNYA'),
			(3066, '2', '08', '02', '99', '000', 'ALAT PENGEBORAN NON MESIN LAINNYA'),
			(3067, '2', '08', '02', '99', '999', 'ALAT PENGEBORAN NON MESIN LAINNYA'),
			(3068, '2', '09', '00', '00', '000', '\"ALAT PRODUKSI'),
			(3069, '2', '09', '01', '00', '000', 'SUMUR'),
			(3070, '2', '09', '01', '01', '000', 'PERALATAN SUMUR MINYAK'),
			(3071, '2', '09', '01', '01', '001', 'ALAT PERAWAT SUMUR'),
			(3072, '2', '09', '01', '01', '002', 'AMERADA TEST'),
			(3073, '2', '09', '01', '01', '003', 'SONOLOG'),
			(3074, '2', '09', '01', '01', '004', 'PERFORMING UNIT'),
			(3075, '2', '09', '01', '01', '005', 'LOGGING UNIT'),
			(3076, '2', '09', '01', '01', '006', 'SAND PUMP'),
			(3077, '2', '09', '01', '01', '999', 'PERALATAN SUMUR MINYAK LAINNYA'),
			(3078, '2', '09', '01', '02', '000', 'SUMUR PEMBORAN'),
			(3079, '2', '09', '01', '02', '001', 'SUMUR PEMBORAN PANAS BUMI'),
			(3080, '2', '09', '01', '02', '002', 'SUMUR PEMBORAN GAS'),
			(3081, '2', '09', '01', '02', '003', 'SUMUR PEMBORAN AIR'),
			(3082, '2', '09', '01', '02', '999', 'SUMUR PEMBORAN LAINNYA'),
			(3083, '2', '09', '01', '99', '000', 'SUMUR LAINNYA'),
			(3084, '2', '09', '01', '99', '999', 'SUMUR LAINNYA'),
			(3085, '2', '09', '02', '00', '000', 'PRODUKSI'),
			(3086, '2', '09', '02', '01', '000', 'R I G'),
			(3087, '2', '09', '02', '01', '001', 'STANG BOR'),
			(3088, '2', '09', '02', '01', '999', 'R I G LAINNYA'),
			(3089, '2', '09', '02', '99', '000', 'PRODUKSI LAINNYA'),
			(3090, '2', '09', '02', '99', '999', 'PRODUKSI LAINNYA'),
			(3091, '2', '09', '03', '00', '000', 'PENGOLAHAN DAN PEMURNIAN'),
			(3092, '2', '09', '03', '01', '000', 'ALAT PENGOLAHAN MINYAK'),
			(3093, '2', '09', '03', '01', '001', 'KAPASITAS KECIL (ALAT PENGOLAHAN MINYAK)'),
			(3094, '2', '09', '03', '01', '002', 'KAPASITAS SEDANG (ALAT PENGOLAHAN MINYAK)'),
			(3095, '2', '09', '03', '01', '003', 'KAPASITAS BESAR (ALAT PENGOLAHAN MINYAK)'),
			(3096, '2', '09', '03', '01', '004', 'CALON EVAPORATOR'),
			(3097, '2', '09', '03', '01', '005', 'CONDENSOR (ALAT PENGOLAHAN MINYAK)'),
			(3098, '2', '09', '03', '01', '006', 'COOLER (ALAT PENGOLAHAN MINYAK)'),
			(3099, '2', '09', '03', '01', '007', 'POMPA PROSO'),
			(3100, '2', '09', '03', '01', '008', 'TURBINE'),
			(3101, '2', '09', '03', '01', '009', 'AIR DRYER'),
			(3102, '2', '09', '03', '01', '010', 'BOILER'),
			(3103, '2', '09', '03', '01', '999', 'ALAT PENGOLAHAN MINYAK LAINNYA'),
			(3104, '2', '09', '03', '02', '000', 'ALAT PENGOLAHAN AIR'),
			(3105, '2', '09', '03', '02', '001', 'KAPASITAS KECIL (ALAT PENGOLAHAN AIR)'),
			(3106, '2', '09', '03', '02', '002', 'KAPASITAS SEDANG (ALAT PENGOLAHAN AIR)'),
			(3107, '2', '09', '03', '02', '003', 'KAPASITAS BESAR (ALAT PENGOLAHAN AIR)'),
			(3108, '2', '09', '03', '02', '999', 'ALAT PENGOLAHAN AIR LAINNYA'),
			(3109, '2', '09', '03', '03', '000', 'ALAT PENGOLAHAN STEAM'),
			(3110, '2', '09', '03', '03', '001', 'KAPASITAS KECIL (ALAT PENGOLAHAN STEAM)'),
			(3111, '2', '09', '03', '03', '002', 'KAPASITAS SEDANG (ALAT PENGOLAHAN STEAM)'),
			(3112, '2', '09', '03', '03', '003', 'KAPASITAS BESAR (ALAT PENGOLAHAN STEAM)'),
			(3113, '2', '09', '03', '03', '999', 'ALAT PENGOLAHAN STEAM LAINNYA'),
			(3114, '2', '09', '03', '04', '000', 'ALAT PENGOLAHAN WAX'),
			(3115, '2', '09', '03', '04', '001', 'KAPASITAS KECIL (ALAT PENGOLAHAN WAX)'),
			(3116, '2', '09', '03', '04', '002', 'KAPASITAS SEDANG (ALAT PENGOLAHAN WAX)'),
			(3117, '2', '09', '03', '04', '003', 'KAPASITAS BESAR (ALAT PENGOLAHAN WAX)'),
			(3118, '2', '09', '03', '04', '999', 'ALAT PENGOLAHAN WAX LAINNYA'),
			(3119, '2', '09', '03', '99', '000', 'PENGOLAHAN DAN PEMURNIAN LAINNYA'),
			(3120, '2', '09', '03', '99', '999', 'PENGOLAHAN DAN PEMURNIAN LAINNYA'),
			(3121, '2', '10', '00', '00', '000', 'PERALATAN OLAH RAGA'),
			(3122, '2', '10', '01', '00', '000', 'PERALATAN OLAH RAGA'),
			(3123, '2', '10', '01', '01', '000', 'PERALATAN OLAH RAGA ATLETIK'),
			(3124, '2', '10', '01', '01', '001', 'LEMPAR CAKRAM'),
			(3125, '2', '10', '01', '01', '002', 'LEMPAR LEMBING'),
			(3126, '2', '10', '01', '01', '003', 'TOLAK PELURU'),
			(3127, '2', '10', '01', '01', '004', 'ALAT LARI GAWANG'),
			(3128, '2', '10', '01', '01', '005', 'GALAH'),
			(3129, '2', '10', '01', '01', '006', 'MARTIL'),
			(3130, '2', '10', '01', '01', '007', 'MISTAR LOMPAT TINGGI'),
			(3131, '2', '10', '01', '01', '008', 'MATRAS LARI'),
			(3132, '2', '10', '01', '01', '009', 'START BLOCK'),
			(3133, '2', '10', '01', '01', '010', 'METER LINE'),
			(3134, '2', '10', '01', '01', '011', 'BENDERA START'),
			(3135, '2', '10', '01', '01', '012', 'PULL MASTER'),
			(3136, '2', '10', '01', '01', '013', 'KOSTUM'),
			(3137, '2', '10', '01', '01', '014', 'BAK LOMPAT TINGGI'),
			(3138, '2', '10', '01', '01', '015', 'BAK LOMPAT JAUH'),
			(3139, '2', '10', '01', '01', '999', 'PERALATAN ATLETIK LAINNYA'),
			(3140, '2', '10', '01', '02', '000', 'PERALATAN PERMAINAN'),
			(3141, '2', '10', '01', '02', '001', 'ALAT TENIS MEJA'),
			(3142, '2', '10', '01', '02', '002', 'ALAT VOLLEY'),
			(3143, '2', '10', '01', '02', '003', 'ALAT BILYARD'),
			(3144, '2', '10', '01', '02', '004', 'ALAT BADMINTON'),
			(3145, '2', '10', '01', '02', '005', 'SEPATU RODA'),
			(3146, '2', '10', '01', '02', '006', 'BOLA KAKI'),
			(3147, '2', '10', '01', '02', '007', 'ALAT BASKET'),
			(3148, '2', '10', '01', '02', '008', 'BOLA BASKET'),
			(3149, '2', '10', '01', '02', '009', 'KERANJANG BOLA/RING'),
			(3150, '2', '10', '01', '02', '010', 'KOSTUM BASKET'),
			(3151, '2', '10', '01', '02', '011', 'KOSTUM SEPAK BOLA'),
			(3152, '2', '10', '01', '02', '012', 'SEPATU BOLA + KAOS KAKI'),
			(3153, '2', '10', '01', '02', '013', 'RAKET TENIS'),
			(3154, '2', '10', '01', '02', '014', 'NET TENIS'),
			(3155, '2', '10', '01', '02', '015', 'BOLA TENIS'),
			(3156, '2', '10', '01', '02', '016', 'ROOT'),
			(3157, '2', '10', '01', '02', '999', 'PERALATAN PERMAINAN LAINNYA'),
			(3158, '2', '10', '01', '03', '000', 'PERALATAN SENAM'),
			(3159, '2', '10', '01', '03', '001', 'PALANG SEJAJAR'),
			(3160, '2', '10', '01', '03', '002', 'PALANG KUDA'),
			(3161, '2', '10', '01', '03', '003', 'MATRAS'),
			(3162, '2', '10', '01', '03', '004', 'GELANG-GELANG'),
			(3163, '2', '10', '01', '03', '005', 'PERALATAN FITNES'),
			(3164, '2', '10', '01', '03', '006', 'KUDA PELANA'),
			(3165, '2', '10', '01', '03', '007', 'BALANCE BEEM'),
			(3166, '2', '10', '01', '03', '008', 'MULTI STATION'),
			(3167, '2', '10', '01', '03', '009', 'ARGOCYCLE'),
			(3168, '2', '10', '01', '03', '010', 'TREADMILL'),
			(3169, '2', '10', '01', '03', '011', 'ORBITREK'),
			(3170, '2', '10', '01', '03', '012', 'HENG UP BOARD'),
			(3171, '2', '10', '01', '03', '013', 'SIT UP BOARD'),
			(3172, '2', '10', '01', '03', '014', 'BECK UP BOARD'),
			(3173, '2', '10', '01', '03', '015', 'DAMBLE SET'),
			(3174, '2', '10', '01', '03', '016', 'BARBLE SET'),
			(3175, '2', '10', '01', '03', '017', 'RAK DAMBLE SET'),
			(3176, '2', '10', '01', '03', '018', 'RAK DARBLE SET'),
			(3177, '2', '10', '01', '03', '019', 'MASSAGE CHAIR'),
			(3178, '2', '10', '01', '03', '020', 'MASSAGE FOOT'),
			(3179, '2', '10', '01', '03', '999', 'PERALATAN SENAM LAINNYA'),
			(3180, '2', '10', '01', '04', '000', 'PARALATAN OLAH RAGA AIR'),
			(3181, '2', '10', '01', '04', '001', 'SKI AIR'),
			(3182, '2', '10', '01', '04', '002', 'SKI DIVING'),
			(3183, '2', '10', '01', '04', '003', 'SELANCAR'),
			(3184, '2', '10', '01', '04', '004', 'PERAHU KARET (PARALATAN OLAH RAGA AIR)'),
			(3185, '2', '10', '01', '04', '005', 'PERAHU LAYAR'),
			(3186, '2', '10', '01', '04', '006', 'ALAT ARUNG JERAM'),
			(3187, '2', '10', '01', '04', '007', 'ALAT DAYUNG'),
			(3188, '2', '10', '01', '04', '008', 'KACA MATA AIR'),
			(3189, '2', '10', '01', '04', '009', 'FULL FOOT FIN'),
			(3190, '2', '10', '01', '04', '010', 'ALAT UKUR KEDALAMAN'),
			(3191, '2', '10', '01', '04', '011', 'BOUYANCE KOMPENSATOR'),
			(3192, '2', '10', '01', '04', '012', 'HP KOMPRESSOR'),
			(3193, '2', '10', '01', '04', '013', 'KOMPAS SELAM'),
			(3194, '2', '10', '01', '04', '014', 'PISAU SELAM'),
			(3195, '2', '10', '01', '04', '015', 'PERAYU KAYAK 1'),
			(3196, '2', '10', '01', '04', '016', 'PERAYU KAYAK 2'),
			(3197, '2', '10', '01', '04', '017', 'PERAHU CANO CANADIAN 1'),
			(3198, '2', '10', '01', '04', '018', 'PERAHU CANO CANADIAN 2'),
			(3199, '2', '10', '01', '04', '019', 'PERAHU TRADISIONAL/PERAHU NAGA'),
			(3200, '2', '10', '01', '04', '020', 'ROOWING/SINGLE SCOOL'),
			(3201, '2', '10', '01', '04', '021', 'ROOWING/DOUBLE SCOOL'),
			(3202, '2', '10', '01', '04', '022', 'PAPAN JUMPING + TALI + HELM'),
			(3203, '2', '10', '01', '04', '023', 'PAPAN SLALOM + TALI + HELM'),
			(3204, '2', '10', '01', '04', '024', 'PAPAN TRICK + TALI + HELM'),
			(3205, '2', '10', '01', '04', '025', 'JAMPING TRACK'),
			(3206, '2', '10', '01', '04', '026', 'MOTOR PENARIK/SPEED BOAT'),
			(3207, '2', '10', '01', '04', '027', 'PELAMPUNG LINTASAN'),
			(3208, '2', '10', '01', '04', '028', 'OPTIMIST'),
			(3209, '2', '10', '01', '04', '029', 'ENTERPRISE'),
			(3210, '2', '10', '01', '04', '030', 'KELAS 420'),
			(3211, '2', '10', '01', '04', '031', 'KELAS 470'),
			(3212, '2', '10', '01', '04', '032', 'FIREBALL'),
			(3213, '2', '10', '01', '04', '033', 'SELANCAR ANGIN'),
			(3214, '2', '10', '01', '04', '034', 'HOBBY CAT'),
			(3215, '2', '10', '01', '04', '035', 'KIIL BOAT'),
			(3216, '2', '10', '01', '04', '999', 'PARALATAN OLAH RAGA AIR LAINNYA'),
			(3217, '2', '10', '01', '05', '000', 'PERALATAN OLAH RAGA UDARA'),
			(3218, '2', '10', '01', '05', '001', 'GANTOLE'),
			(3219, '2', '10', '01', '05', '002', 'BALON UDARA'),
			(3220, '2', '10', '01', '05', '003', 'PAYUNG UDARA (PARASUT)'),
			(3221, '2', '10', '01', '05', '004', 'ALAT TERBANG LAYANG'),
			(3222, '2', '10', '01', '05', '999', 'PERALATAN OLAH RAGA UDARA LAINNYA'),
			(3223, '2', '10', '01', '06', '000', 'PERALATAN OLAH RAGA LAINNYA'),
			(3224, '2', '10', '01', '06', '001', 'CATUR'),
			(3225, '2', '10', '01', '06', '002', 'SARUNG TINJU'),
			(3226, '2', '10', '01', '06', '003', 'SEPEDA OLAH RAGA'),
			(3227, '2', '10', '01', '06', '999', 'PERALATAN OLAH RAGA LAINNYA'),
			(3228, '2', '10', '01', '99', '999', 'PERALATAN OLAH RAGA LAINNYA'),
			(3229, '3', '00', '00', '00', '000', 'GEDUNG DAN BANGUNAN'),
			(3230, '3', '01', '00', '00', '000', 'BANGUNAN GEDUNG'),
			(3231, '3', '01', '01', '00', '000', 'BANGUNAN GEDUNG TEMPAT KERJA'),
			(3232, '3', '01', '01', '01', '000', 'BANGUNAN GEDUNG KANTOR'),
			(3233, '3', '01', '01', '01', '001', 'BANGUNAN GEDUNG KANTOR PERMANEN'),
			(3234, '3', '01', '01', '01', '002', 'BANGUNAN GEDUNG KANTOR SEMI PERMANEN'),
			(3235, '3', '01', '01', '01', '003', 'BANGUNAN GEDUNG KANTOR DARURAT'),
			(3236, '3', '01', '01', '01', '004', 'RUMAH PANEL'),
			(3237, '3', '01', '01', '01', '999', 'BANGUNAN GEDUNG KANTOR LAINNYA'),
			(3238, '3', '01', '01', '02', '000', 'BANGUNAN GUDANG'),
			(3239, '3', '01', '01', '02', '001', 'BANGUNAN GUDANG TERTUTUP PERMANEN'),
			(3240, '3', '01', '01', '02', '002', 'BANGUNAN GUDANG TERTUTUP SEMI PERMANEN'),
			(3241, '3', '01', '01', '02', '003', 'BANGUNAN GUDANG TERTUTUP DARURAT'),
			(3242, '3', '01', '01', '02', '004', 'BANGUNAN GUDANG TERBUKA PERMANEN'),
			(3243, '3', '01', '01', '02', '005', 'BANGUNAN GUDANG TERBUKA SEMI PERMANEN'),
			(3244, '3', '01', '01', '02', '006', 'BANGUNAN GUDANG TERBUKA DARURAT'),
			(3245, '3', '01', '01', '02', '999', 'BANGUNAN GUDANG LAINNYA'),
			(3246, '3', '01', '01', '03', '000', 'BANGUNAN GEDUNG UNTUK BENGKEL'),
			(3247, '3', '01', '01', '03', '001', 'BANGUNAN BENGKEL PERMANEN'),
			(3248, '3', '01', '01', '03', '002', 'BANGUNAN BENGKEL  SEMI PERMANEN'),
			(3249, '3', '01', '01', '03', '003', 'BANGUNAN BENGKEL  DARURAT'),
			(3250, '3', '01', '01', '03', '999', 'BANGUNAN GEDUNG UNTUK BENGKEL LAINNYA'),
			(3251, '3', '01', '01', '04', '000', 'BANGUNAN GEDUNG INSTALASI'),
			(3252, '3', '01', '01', '04', '001', 'GEDUNG INSTALASI STUDIO'),
			(3253, '3', '01', '01', '04', '002', 'GEDUNG INSTALASI PEMANCAR'),
			(3254, '3', '01', '01', '04', '999', 'BANGUNAN GEDUNG INSTALASI LAINNYA'),
			(3255, '3', '01', '01', '05', '000', 'BANGUNAN GEDUNG LABORATORIUM'),
			(3256, '3', '01', '01', '05', '001', 'BANGUNAN GEDUNG LABORATORIUM PERMANEN'),
			(3257, '3', '01', '01', '05', '002', 'BANGUNAN GEDUNG LABORATORIUM SEMI PERMANEN'),
			(3258, '3', '01', '01', '05', '003', 'BANGUNAN GEDUNG LABORATORIUM DARURAT'),
			(3259, '3', '01', '01', '05', '999', 'BANGUNAN GEDUNG LABORATORIUM LAINNYA'),
			(3260, '3', '01', '01', '06', '000', 'BANGUNAN KESEHATAN'),
			(3261, '3', '01', '01', '06', '001', 'BANGUNAN POSYANDU'),
			(3262, '3', '01', '01', '06', '002', 'BANGUNAN POLINDES (PONDOK BERSALIN DESA)'),
			(3263, '3', '01', '01', '06', '003', 'BANGUNAN APOTIK'),
			(3264, '3', '01', '01', '06', '004', 'BANGUNAN TOKO KHUSUS OBAT/JAMU'),
			(3265, '3', '01', '01', '06', '999', 'BANGUNAN KESEHATAN LAINNYA'),
			(3266, '3', '01', '01', '07', '000', 'BANGUNAN GEDUNG TEMPAT IBADAH'),
			(3267, '3', '01', '01', '07', '001', 'BANGUNAN GEDUNG TEMPAT IBADAH PERMANEN'),
			(3268, '3', '01', '01', '07', '002', 'BANGUNAN GEDUNG TEMPAT IBADAH SEMI PERMANEN'),
			(3269, '3', '01', '01', '07', '003', 'BANGUNAN GEDUNG TEMPAT IBADAH DARURAT'),
			(3270, '3', '01', '01', '07', '999', 'BANGUNAN GEDUNG TEMPAT IBADAH LAINNYA'),
			(3271, '3', '01', '01', '08', '000', 'BANGUNAN GEDUNG TEMPAT PERTEMUAN'),
			(3272, '3', '01', '01', '08', '001', 'BANGUNAN GEDUNG PERTEMUAN PERMANEN'),
			(3273, '3', '01', '01', '08', '002', 'BANGUNAN GEDUNG PERTEMUAN SEMI PERMANEN'),
			(3274, '3', '01', '01', '08', '003', 'BANGUNAN GEDUNG PERTEMUAN DARURAT'),
			(3275, '3', '01', '01', '08', '999', 'BANGUNAN GEDUNG TEMPAT PERTEMUAN LAINNYA'),
			(3276, '3', '01', '01', '09', '000', 'BANGUNAN GEDUNG TEMPAT PENDIDIKAN'),
			(3277, '3', '01', '01', '09', '001', 'BANGUNAN GEDUNG PENDIDIKAN PERMANEN'),
			(3278, '3', '01', '01', '09', '002', 'BANGUNAN GEDUNG PENDIDIKAN SEMI PERMANEN'),
			(3279, '3', '01', '01', '09', '003', 'BANGUNAN GEDUNG PENDIDIKAN DARURAT'),
			(3280, '3', '01', '01', '09', '004', 'BANGUNAN GEDUNG PENDIDIKAN DAN LATIHAN'),
			(3281, '3', '01', '01', '09', '999', 'BANGUNAN GEDUNG TEMPAT PENDIDIKAN LAINNYA'),
			(3282, '3', '01', '01', '10', '000', 'BANGUNAN GEDUNG TEMPAT OLAH RAGA'),
			(3283, '3', '01', '01', '10', '001', 'GEDUNG OLAH RAGA TETUTUP PERMANEN'),
			(3284, '3', '01', '01', '10', '002', 'GEDUNG OLAH RAGA TERTUTUP SEMI PERMANEN'),
			(3285, '3', '01', '01', '10', '003', 'GEDUNG OLAH RAGA TERTUTUP DARURAT'),
			(3286, '3', '01', '01', '10', '004', 'BANGUNAN OLAH RAGA TERBUKA PERMANEN'),
			(3287, '3', '01', '01', '10', '005', 'BANGUNAN OLAH RAGA TERBUKA SEMI PERMANEN'),
			(3288, '3', '01', '01', '10', '006', 'BANGUNAN OLAH RAGA TERBUKA DARURAT'),
			(3289, '3', '01', '01', '10', '007', 'BANGUNAN GEDUNG OLAH RAGA KOLAM RENANG'),
			(3290, '3', '01', '01', '10', '999', 'BANGUNAN GEDUNG TEMPAT OLAH RAGA LAINNYA'),
			(3291, '3', '01', '01', '11', '000', 'BANGUNAN GEDUNG PERTOKOAN/KOPERASI/PASAR'),
			(3292, '3', '01', '01', '11', '001', 'GEDUNG PERTOKOAN/KOPERASI/PASAR PERMANEN'),
			(3293, '3', '01', '01', '11', '002', 'GEDUNG PERTOKOAN/KOPERASI/PASAR SEMI PERMANEN'),
			(3294, '3', '01', '01', '11', '003', 'GEDUNG PERTOKOAN/KOPERASI/PASAR DARURAT'),
			(3295, '3', '01', '01', '11', '999', 'BANGUNAN GEDUNG PERTOKOAN/KOPERASI/PASAR LAINNYA'),
			(3296, '3', '01', '01', '12', '000', 'BANGUNAN GEDUNG GARASI/POOL'),
			(3297, '3', '01', '01', '12', '001', 'GEDUNG GARASI/POOL PERMANEN'),
			(3298, '3', '01', '01', '12', '002', 'GEDUNG GARASI/POOL SEMI PERMANEN'),
			(3299, '3', '01', '01', '12', '003', 'GEDUNG GARASI/POOL DARURAT'),
			(3300, '3', '01', '01', '12', '999', 'BANGUNAN GEDUNG GARASI/POOL LAINNYA'),
			(3301, '3', '01', '01', '13', '000', 'BANGUNAN GEDUNG PEMOTONG HEWAN'),
			(3302, '3', '01', '01', '13', '001', 'GEDUNG PEMOTONG HEWAN PERMANEN'),
			(3303, '3', '01', '01', '13', '002', 'GEDUNG PEMOTONG HEWAN SEMI PERMANEN'),
			(3304, '3', '01', '01', '13', '003', 'GEDUNG PEMOTONG HEWAN DARURAT'),
			(3305, '3', '01', '01', '13', '999', 'BANGUNAN GEDUNG PEMOTONG HEWAN LAINNYA'),
			(3306, '3', '01', '01', '14', '000', 'BANGUNAN GEDUNG PERPUSTAKAAN'),
			(3307, '3', '01', '01', '14', '001', 'BANGUNAN GEDUNG PERPUSTAKAAN PERMANEN'),
			(3308, '3', '01', '01', '14', '002', 'BANGUNAN GEDUNG PERPUSTAKAAN SEMI PERMANEN'),
			(3309, '3', '01', '01', '14', '003', 'BANGUNAN GEDUNG PERPUSTAKAAN DARURAT'),
			(3310, '3', '01', '01', '14', '999', 'BANGUNAN GEDUNG PERPUSTAKAAN LAINNYA'),
			(3311, '3', '01', '01', '15', '000', 'BANGUNAN GEDUNG MUSIUM'),
			(3312, '3', '01', '01', '15', '001', 'BANGUNAN GEDUNG MUSIUM PERMANEN'),
			(3313, '3', '01', '01', '15', '002', 'BANGUNAN GEDUNG MUSIUM SEMI PERMANEN'),
			(3314, '3', '01', '01', '15', '003', 'BANGUNAN GEDUNG MUSIUM DARURAT'),
			(3315, '3', '01', '01', '15', '999', 'BANGUNAN GEDUNG MUSIUM LAINNYA'),
			(3316, '3', '01', '01', '16', '000', 'BANGUNAN GEDUNG TERMINAL/PELABUHAN'),
			(3317, '3', '01', '01', '16', '001', 'BANGUNAN GEDUNG TERMINAL/PELABUHAN PERMANEN'),
			(3318, '3', '01', '01', '16', '002', 'BANGUNAN GEDUNG TERMINAL/PELABUHAN SEMI PERMANEN'),
			(3319, '3', '01', '01', '16', '003', 'BANGUNAN GEDUNG TERMINAL/PELABUHAN DARURAT'),
			(3320, '3', '01', '01', '16', '004', 'BANGUNAN HALTE/SHELTER'),
			(3321, '3', '01', '01', '16', '999', 'BANGUNAN GEDUNG TERMINAL/PELABUHAN LAINNYA'),
			(3322, '3', '01', '01', '17', '000', 'BANGUNAN TERBUKA'),
			(3323, '3', '01', '01', '17', '001', 'BANGUNAN LANTAI JEMUR PERMANEN'),
			(3324, '3', '01', '01', '17', '002', 'BANGUNAN LANTAI JEMUR SEMI PERMANEN'),
			(3325, '3', '01', '01', '17', '003', 'BANGUNAN LANTAI JEMUR DARURAT'),
			(3326, '3', '01', '01', '17', '999', 'BANGUNAN TERBUKA LAINNYA'),
			(3327, '3', '01', '01', '18', '000', 'BANGUNAN PENAMPUNG SEKAM'),
			(3328, '3', '01', '01', '18', '001', 'BANGUNAN PENAMPUNG SEKAM PERMANEN'),
			(3329, '3', '01', '01', '18', '002', 'BANGUNAN PENAMPUNG SEKAM SEMI PERMANEN'),
			(3330, '3', '01', '01', '18', '003', 'BANGUNAN PENAMPUNG SEKAM DARURAT'),
			(3331, '3', '01', '01', '18', '999', 'BANGUNAN PENAMPUNG SEKAM LAINNYA'),
			(3332, '3', '01', '01', '19', '000', 'BANGUNAN TEMPAT PELELANGAN IKAN (TPI)'),
			(3333, '3', '01', '01', '19', '001', 'BANGUNAN TPI PERMANEN'),
			(3334, '3', '01', '01', '19', '002', 'BANGUNAN TPI SEMI PERMANEN'),
			(3335, '3', '01', '01', '19', '003', 'BANGUNAN TPI DARURAT'),
			(3336, '3', '01', '01', '19', '999', 'BANGUNAN TEMPAT PELELANGAN IKAN (TPI) LAINNYA'),
			(3337, '3', '01', '01', '20', '000', 'BANGUNAN INDUSTRI'),
			(3338, '3', '01', '01', '20', '001', 'BANGUNAN INDUSTRI MAKANAN'),
			(3339, '3', '01', '01', '20', '002', 'BANGUNAN INDUSTRI MINUMAN'),
			(3340, '3', '01', '01', '20', '003', 'BANGUNAN INDUSTRI ALAT RT'),
			(3341, '3', '01', '01', '20', '004', 'BANGUNAN INDUSTRI PAKAIAN/GARMENT'),
			(3342, '3', '01', '01', '20', '005', 'BANGUNAN INDUSTRI BAJA/BESI/LOGAM'),
			(3343, '3', '01', '01', '20', '006', 'BANGUNAN INDUSTRI PENGEMASAN'),
			(3344, '3', '01', '01', '20', '007', 'BANGUNAN INDUSTRI BENGKEL'),
			(3345, '3', '01', '01', '20', '008', 'BANGUNAN INDUSTRI PENYULINGAN MINYAK'),
			(3346, '3', '01', '01', '20', '009', 'BANGUNAN INDUSTRI KIMIA DAN PUPUK'),
			(3347, '3', '01', '01', '20', '010', 'BANGUNAN INDUSTRI OBAT-OBATAN'),
			(3348, '3', '01', '01', '20', '011', 'BANGUNAN INDUSTRI SEMEN'),
			(3349, '3', '01', '01', '20', '012', 'BANGUNAN INDUSTRI BATU-BATA/BATAKO'),
			(3350, '3', '01', '01', '20', '013', 'BANGUNAN INDUSTRI GENTENG'),
			(3351, '3', '01', '01', '20', '014', 'BANGUNAN INDUSTRI PERCETAKAN'),
			(3352, '3', '01', '01', '20', '015', 'BANGUNAN INDUSTRI TEKSTIL'),
			(3353, '3', '01', '01', '20', '016', 'BANGUNAN INDUSTRI ALAT OLAH RAGA'),
			(3354, '3', '01', '01', '20', '017', 'BANGUNAN INDUSTRI KENDARAAN/OTOMOTIF'),
			(3355, '3', '01', '01', '20', '018', 'BANGUNAN INDUSTRI KERAMIK/MARMER'),
			(3356, '3', '01', '01', '20', '019', 'BANGUNAN PABRIK ES'),
			(3357, '3', '01', '01', '20', '020', 'BANGUNAN PASAR IKAN HIGIENIS/ PIH'),
			(3358, '3', '01', '01', '20', '021', 'BANGUNAN DEPO PASAR IKAN'),
			(3359, '3', '01', '01', '20', '022', 'BANGUNAN PASAR/ RAISER IKAN HIAS'),
			(3360, '3', '01', '01', '20', '999', 'BANGUNAN INDUSTRI LAINNYA'),
			(3361, '3', '01', '01', '21', '000', 'BANGUNAN PETERNAKAN/PERIKANAN'),
			(3362, '3', '01', '01', '21', '001', 'BANGUNAN UNTUK KANDANG'),
			(3363, '3', '01', '01', '21', '002', 'BANGUNAN KOLAM/BAK IKAN'),
			(3364, '3', '01', '01', '21', '003', 'BANGUNAN PEMBESAR IKAN'),
			(3365, '3', '01', '01', '21', '999', 'BANGUNAN PETERNAKAN/PERIKANAN LAINNYA'),
			(3366, '3', '01', '01', '22', '000', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA'),
			(3367, '3', '01', '01', '22', '001', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA PERMANEN'),
			(3368, '3', '01', '01', '22', '002', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA SEMI PERMANEN'),
			(3369, '3', '01', '01', '22', '003', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA DARURAT'),
			(3370, '3', '01', '01', '22', '004', 'GEDUNG PENGUJIAN KENDARAAN LAINNYA'),
			(3371, '3', '01', '01', '22', '999', 'BANGUNAN LAINNYA'),
			(3372, '3', '01', '01', '23', '000', 'BANGUNAN FASILITAS UMUM'),
			(3373, '3', '01', '01', '23', '001', 'BANGUNAN TEMPAT PARKIR'),
			(3374, '3', '01', '01', '23', '002', 'BANGUNAN TEMPAT BERMAIN ANAK'),
			(3375, '3', '01', '01', '23', '003', 'BANGUNAN PENERANGAN JALAN'),
			(3376, '3', '01', '01', '23', '004', 'BANGUNAN PENERANGAN TAMAN'),
			(3377, '3', '01', '01', '23', '999', 'BANGUNAN FASILITAS UMUM LAINNYA'),
			(3378, '3', '01', '01', '24', '000', 'BANGUNAN PARKIR'),
			(3379, '3', '01', '01', '24', '001', 'BANGUNAN PARKIR TERBUKA PERMANEN'),
			(3380, '3', '01', '01', '24', '002', 'BANGUNAN PARKIR TERBUKA SEMI PERMANEN'),
			(3381, '3', '01', '01', '24', '003', 'BANGUNAN PARKIR TERBUKA DARURAT'),
			(3382, '3', '01', '01', '24', '004', 'BANGUNAN PARKIR TERTUTUP PERMANEN'),
			(3383, '3', '01', '01', '24', '005', 'BANGUNAN PARKIR TERTUTUP SEMI PERMANEN'),
			(3384, '3', '01', '01', '24', '006', 'BANGUNAN PARKIR TERTUTUP DARURAT'),
			(3385, '3', '01', '01', '24', '999', 'BANGUNAN PARKIR LAINNYA'),
			(3386, '3', '01', '01', '25', '000', 'TAMAN'),
			(3387, '3', '01', '01', '25', '001', 'TAMAN PERMANEN'),
			(3388, '3', '01', '01', '25', '002', 'TAMAN SEMI PERMANEN'),
			(3389, '3', '01', '01', '25', '999', 'TAMAN LAINNYA'),
			(3390, '3', '01', '01', '99', '000', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA'),
			(3391, '3', '01', '01', '99', '999', 'BANGUNAN GEDUNG TEMPAT KERJA LAINNYA'),
			(3392, '3', '01', '02', '01', '000', 'HOTEL'),
			(3393, '3', '01', '02', '01', '001', 'HOTEL PERMANEN'),
			(3394, '3', '01', '02', '01', '002', 'HOTEL SEMI PERMANEN'),
			(3395, '3', '01', '02', '01', '999', 'HOTEL LAINNYA'),
			(3396, '3', '01', '02', '02', '000', 'MOTEL'),
			(3397, '3', '01', '02', '02', '001', 'MOTEL PERMANEN'),
			(3398, '3', '01', '02', '02', '002', 'MOTEL SEMI PERMANEN'),
			(3399, '3', '01', '02', '02', '999', 'MOTEL LAINNYA'),
			(3400, '3', '01', '02', '03', '000', 'PANTI ASUHAN'),
			(3401, '3', '01', '02', '03', '001', 'PANTI ASUHAN'),
			(3402, '3', '01', '02', '03', '999', 'PANTI ASUHAN LAINNYA'),
			(3403, '3', '01', '02', '99', '000', 'BANGUNAN GEDUNG TEMPAT TINGGAL LAINNYA'),
			(3404, '3', '01', '02', '99', '999', 'BANGUNAN GEDUNG TEMPAT TINGGAL LAINNYA'),
			(3405, '3', '02', '00', '00', '000', 'MONUMEN'),
			(3406, '3', '02', '01', '00', '000', 'CANDI/TUGU PERINGATAN/PRASASTI'),
			(3407, '3', '02', '01', '01', '000', 'CANDI'),
			(3408, '3', '02', '01', '01', '001', 'CANDI'),
			(3409, '3', '02', '01', '01', '999', 'CANDI LAINNYA'),
			(3410, '3', '02', '01', '02', '000', 'TUGU'),
			(3411, '3', '02', '01', '02', '001', 'TUGU KEMERDEKAAN'),
			(3412, '3', '02', '01', '02', '002', 'TUGU PEMBANGUNAN'),
			(3413, '3', '02', '01', '02', '999', 'TUGU PERINGATAN LAINNYA'),
			(3414, '3', '02', '01', '03', '000', 'BANGUNAN PENINGGALAN'),
			(3415, '3', '02', '01', '03', '001', 'ISTANA PENINGGALAN'),
			(3416, '3', '02', '01', '03', '002', 'RUMAH ADAT'),
			(3417, '3', '02', '01', '03', '003', 'RUMAH PENINGGALAN SEJARAH'),
			(3418, '3', '02', '01', '03', '004', 'MAKAM BERSEJARAH'),
			(3419, '3', '02', '01', '03', '999', 'BANGUNAN PENINGGALAN LAINNYA'),
			(3420, '3', '02', '01', '99', '000', 'CANDI/TUGU PERINGATAN/PRASASTI LAINNYA'),
			(3421, '3', '02', '01', '99', '999', 'BANGUNAN PENINGGALAN LAINNYA'),
			(3422, '3', '02', '01', '99', '999', 'CANDI/TUGU PERINGATAN/PRASASTI LAINNYA'),
			(3423, '4', '00', '00', '00', '000', '\"JALAN'),
			(3424, '4', '01', '00', '00', '000', 'JALAN DAN JEMBATAN'),
			(3425, '4', '01', '01', '00', '000', 'JALAN'),
			(3426, '4', '01', '01', '01', '000', 'JALAN DESA'),
			(3427, '4', '01', '01', '01', '001', 'JALAN DESA'),
			(3428, '4', '01', '01', '01', '999', 'JALAN DESA LAINNYA'),
			(3429, '4', '01', '01', '02', '000', 'JALAN KHUSUS'),
			(3430, '4', '01', '01', '02', '001', 'JALAN KHUSUS INSPEKSI'),
			(3431, '4', '01', '01', '02', '002', 'JALAN KHUSUS KOMPLEKS'),
			(3432, '4', '01', '01', '02', '003', 'JALAN KHUSUS PROYEK'),
			(3433, '4', '01', '01', '02', '004', 'JALAN KHUSUS QUARRY'),
			(3434, '4', '01', '01', '02', '005', 'JALAN KHUSUS LORI'),
			(3435, '4', '01', '01', '02', '006', 'JALAN KHUSUS BADAN HUKUM'),
			(3436, '4', '01', '01', '02', '007', 'JALAN KHUSUS PERORANGAN'),
			(3437, '4', '01', '01', '02', '008', 'JALAN KHUSUS LAINNYA'),
			(3438, '4', '01', '01', '02', '009', 'JALAN KHUSUS PEJALAN KAKI (TROTOAR)'),
			(3439, '4', '01', '01', '02', '999', 'LAINNYA (JALAN KHUSUS)'),
			(3440, '4', '01', '01', '99', '000', 'JALAN LAINNYA'),
			(3441, '4', '01', '01', '99', '999', 'JALAN LAINNYA'),
			(3442, '4', '01', '02', '00', '000', 'JEMBATAN'),
			(3443, '4', '01', '02', '01', '000', 'JEMBATAN PADA JALAN DESA'),
			(3444, '4', '01', '02', '01', '001', 'JEMBATAN PADA JALAN DESA'),
			(3445, '4', '01', '02', '01', '999', 'JEMBATAN PADA JALAN DESA LAINNYA'),
			(3446, '4', '01', '02', '02', '000', 'JEMBATAN PADA JALAN KHUSUS'),
			(3447, '4', '01', '02', '02', '001', 'JEMBATAN PADA JALAN KHUSUS INSPEKSI'),
			(3448, '4', '01', '02', '02', '002', 'JEMBATAN PADA JALAN KHUSUS KOMPLEKS'),
			(3449, '4', '01', '02', '02', '003', 'JEMBATAN PADA JALAN KHUSUS PROYEK'),
			(3450, '4', '01', '02', '02', '004', 'JEMBATAN PADA JALAN KHUSUS QUARRY'),
			(3451, '4', '01', '02', '02', '005', 'JEMBATAN PADA JALAN KHUSUS LORI'),
			(3452, '4', '01', '02', '02', '006', 'JEMBATAN PADA JALAN KHUSUS BADAN HUKUM'),
			(3453, '4', '01', '02', '02', '007', 'JEMBATAN PADA JALAN KHUSUS PERORANGAN'),
			(3454, '4', '01', '02', '02', '999', 'JEMBATAN PADA JALAN KHUSUS LAINNYA'),
			(3455, '4', '01', '02', '03', '000', 'JEMBATAN PENYEBERANGAN'),
			(3456, '4', '01', '02', '03', '001', 'JEMBATAN PENYEBERANGAN ORANG'),
			(3457, '4', '01', '02', '03', '002', 'JEMBATAN PENYEBERANGAN KENDARAAN'),
			(3458, '4', '01', '02', '03', '005', 'JEMBATAN GANTUNG'),
			(3459, '4', '01', '02', '03', '999', 'JEMBATAN PENYEBERANGAN LAINNYA'),
			(3460, '4', '01', '02', '04', '000', 'JEMBATAN LABUH/SANDAR PADA TERMINAL'),
			(3461, '4', '01', '02', '04', '001', 'DERMAGA'),
			(3462, '4', '01', '02', '04', '002', 'KADE'),
			(3463, '4', '01', '02', '04', '003', 'EMBARKASI/DEBARKASI'),
			(3464, '4', '01', '02', '04', '004', 'JEMBATAN PANTAI'),
			(3465, '4', '01', '02', '04', '999', 'JEMBATAN LABUH/SANDAR PADA TERMINAL LAINNYA'),
			(3466, '4', '01', '02', '05', '000', 'JEMBATAN PENGUKUR'),
			(3467, '4', '01', '02', '05', '001', 'JEMBATAN TIMBANG'),
			(3468, '4', '01', '02', '05', '002', 'JEMBATAN KIR/PENGUJIAN'),
			(3469, '4', '01', '02', '05', '999', 'JEMBATAN PENGUKUR LAINNYA'),
			(3470, '4', '01', '02', '99', '000', 'JEMBATAN LAINNYA'),
			(3471, '4', '01', '02', '99', '999', 'JEMBATAN LAINNYA'),
			(3472, '4', '02', '00', '00', '000', 'BANGUNAN AIR'),
			(3473, '4', '02', '01', '00', '000', 'BANGUNAN AIR IRIGASI'),
			(3474, '4', '02', '01', '01', '000', 'BANGUNAN WADUK IRIGASI'),
			(3475, '4', '02', '01', '01', '001', '\"WADUK DENGAN BENDUNGAN'),
			(3476, '4', '02', '01', '01', '002', '\"WADUK DENGAN BENDUNGAN'),
			(3477, '4', '02', '01', '01', '003', 'WADUK DENGAN MENARA PENGAMBILAN'),
			(3478, '4', '02', '01', '01', '004', '\"WADUK DENGAN TANGGUL'),
			(3479, '4', '02', '01', '01', '005', 'WADUK DENGAN TANGGUL DAN PINTU PENGUKUR WADUK LAPANGAN'),
			(3480, '4', '02', '01', '01', '999', 'BANGUNAN WADUK IRIGASI LAINNYA'),
			(3481, '4', '02', '01', '02', '000', 'BANGUNAN PENGAMBILAN IRIGASI'),
			(3482, '4', '02', '01', '02', '001', 'BENDUNG'),
			(3483, '4', '02', '01', '02', '002', 'BENDUNG DENGAN PINTU BILAS'),
			(3484, '4', '02', '01', '02', '003', 'BENDUNG DENGAN POMPA'),
			(3485, '4', '02', '01', '02', '004', 'BANGUNAN PENGAMBILAN BEBAS'),
			(3486, '4', '02', '01', '02', '005', 'BANGUNAN PENGAMBILAN BEBAS DGN POMPA (BGNAN PENGAMBILAN IRIGASI)'),
			(3487, '4', '02', '01', '02', '006', 'SUMUR DENGAN POMPA (BANGUNAN PENGAMBILAN IRIGASI)'),
			(3488, '4', '02', '01', '02', '999', 'BANGUNAN PENGAMBILAN IRIGASI LAINNYA'),
			(3489, '4', '02', '01', '03', '000', 'BANGUNAN PEMBAWA IRIGASI'),
			(3490, '4', '02', '01', '03', '001', 'SALURAN MUKA (BANGUNAN PEMBAWA IRIGASI)'),
			(3491, '4', '02', '01', '03', '002', 'SALURAN INDUK (BANGUNAN PEMBAWA IRIGASI)'),
			(3492, '4', '02', '01', '03', '003', 'SALURAN SEKUNDER (BANGUNAN PEMBAWA IRIGASI)'),
			(3493, '4', '02', '01', '03', '004', 'SALURAN TERSIER (BANGUNAN PEMBAWA IRIGASI)'),
			(3494, '4', '02', '01', '03', '005', 'SALURAN KWARTER'),
			(3495, '4', '02', '01', '03', '006', 'SALURAN PASANG TERTUTUP/TEROWONGAN'),
			(3496, '4', '02', '01', '03', '007', 'SALURAN SUPLESI'),
			(3497, '4', '02', '01', '03', '999', 'BANGUNAN PEMBAWA IRIGASI LAINNYA'),
			(3498, '4', '02', '01', '04', '000', 'BANGUNAN PEMBUANG IRIGASI'),
			(3499, '4', '02', '01', '04', '001', 'SALURAN INDUK PEMBUANG (BANGUNAN PEMBUANG IRIGASI)'),
			(3500, '4', '02', '01', '04', '002', 'SALURAN SEKUNDER PEMBUANG (BANGUNAN PEMBUANG IRIGASI)'),
			(3501, '4', '02', '01', '04', '003', 'SALURAN TERSIER PEMBUANG (BANGUNAN PEMBUANG IRIGASI)'),
			(3502, '4', '02', '01', '04', '999', 'BANGUNAN PEMBUANG IRIGASI LAINNYA'),
			(3503, '4', '02', '01', '05', '000', 'BANGUNAN PENGAMAN IRIGASI'),
			(3504, '4', '02', '01', '05', '001', 'TANGGUL BANJIR (BANGUNAN PENGAMAN IRIGASI)'),
			(3505, '4', '02', '01', '05', '002', 'BANGUNAN PINTU AIR/KLEP (BANGUNAN PENGAMAN IRIGASI)'),
			(3506, '4', '02', '01', '05', '999', 'BANGUNAN PENGAMAN IRIGASI LAINNYA'),
			(3507, '4', '02', '01', '06', '000', 'BANGUNAN PELENGKAP IRIGASI'),
			(3508, '4', '02', '01', '06', '001', 'BANGUNAN BAGI'),
			(3509, '4', '02', '01', '06', '002', 'BANGUNAN BAGI DAN SADAP (BANGUNAN PELENGKAP IRIGASI)'),
			(3510, '4', '02', '01', '06', '003', 'BANGUNAN SADAP (BANGUNAN PELENGKAP IRIGASI)'),
			(3511, '4', '02', '01', '06', '004', 'BANGUNAN GOT MIRING'),
			(3512, '4', '02', '01', '06', '005', 'BANGUNAN TERJUN (BANGUNAN PELENGKAP IRIGASI)'),
			(3513, '4', '02', '01', '06', '006', 'BANGUNAN TALANG (BANGUNAN PELENGKAP IRIGASI)'),
			(3514, '4', '02', '01', '06', '007', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP IRIGASI)'),
			(3515, '4', '02', '01', '06', '008', 'BANGUNAN GORONG-GORONG (BANGUNAN PELENGKAP IRIGASI)'),
			(3516, '4', '02', '01', '06', '009', 'BANGUNAN PELIMPAH SAMPAH'),
			(3517, '4', '02', '01', '06', '010', 'BANGUNAN PENGELUARAN/PINTU'),
			(3518, '4', '02', '01', '06', '011', 'BANGUNAN BOX TERSIER (BANGUNAN PELENGKAP IRIGASI)'),
			(3519, '4', '02', '01', '06', '012', 'BANGUNAN PENGUKUR'),
			(3520, '4', '02', '01', '06', '013', 'BANGUNAN MANDI HEWAN'),
			(3521, '4', '02', '01', '06', '014', 'BANGUNAN PERTEMUAN SALURAN'),
			(3522, '4', '02', '01', '06', '015', 'BANGUNAN PELENGKAP DALAM PETAK TERSIER'),
			(3523, '4', '02', '01', '06', '016', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP IRIGASI)'),
			(3524, '4', '02', '01', '06', '999', 'BANGUNAN PELENGKAP IRIGASI LAINNYA'),
			(3525, '4', '02', '01', '07', '000', 'BANGUNAN SAWAH IRIGASI'),
			(3526, '4', '02', '01', '07', '001', 'BANGUNAN SAWAH IRIGASI TEHNIS'),
			(3527, '4', '02', '01', '07', '002', 'BANGUNAN SAWAH IRIGASI SEMI TEHNIS'),
			(3528, '4', '02', '01', '07', '003', 'BANGUNAN SAWAH IRIGASI NON TEHNIS'),
			(3529, '4', '02', '01', '07', '999', 'BANGUNAN SAWAH IRIGASI LAINNYA'),
			(3530, '4', '02', '01', '99', '000', 'BANGUNAN AIR IRIGASI LAINNYA'),
			(3531, '4', '02', '01', '99', '999', 'BANGUNAN AIR IRIGASI LAINNYA'),
			(3532, '4', '02', '02', '00', '000', 'BANGUNAN PENGAIRAN PASANG SURUT'),
			(3533, '4', '02', '02', '01', '000', 'BANGUNAN WADUK PASANG SURUT'),
			(3534, '4', '02', '02', '01', '001', 'WADUK PASANG SURUT'),
			(3535, '4', '02', '02', '01', '999', 'BANGUNAN WADUK PASANG SURUT LAINNYA'),
			(3536, '4', '02', '02', '02', '000', 'BANGUNAN PENGAMBILAN PASANG SURUT'),
			(3537, '4', '02', '02', '02', '001', 'BANGUNAN BENDUNG DENGAN POMPA'),
			(3538, '4', '02', '02', '02', '002', 'BANGUNAN PENGAMBILAN BEBAS DGN POMPA (BGNAN PENGAMBILAN PSG SURUT'),
			(3539, '4', '02', '02', '02', '999', 'BANGUNAN PENGAMBILAN PASANG SURUT LAINNYA'),
			(3540, '4', '02', '02', '03', '000', 'BANGUNAN PEMBAWA PASANG SURUT'),
			(3541, '4', '02', '02', '03', '001', 'SALURAN MUKA (BANGUNAN PEMBAWA PASANG SURUT)'),
			(3542, '4', '02', '02', '03', '002', 'SALURAN INDUK (BANGUNAN PEMBAWA PASANG SURUT)'),
			(3543, '4', '02', '02', '03', '003', 'SALURAN SEKUNDER (BANGUNAN PEMBAWA PASANG SURUT)'),
			(3544, '4', '02', '02', '03', '004', 'SALURAN TERSIER (BANGUNAN PEMBAWA PASANG SURUT)'),
			(3545, '4', '02', '02', '03', '005', 'SALURAN PENYIMPAN AIR'),
			(3546, '4', '02', '02', '03', '006', 'SALURAN LALU LINTAS AIR'),
			(3547, '4', '02', '02', '03', '999', 'BANGUNAN PEMBAWA PASANG SURUT LAINNYA'),
			(3548, '4', '02', '02', '04', '000', 'SALURAN PEMBUANG PASANG SURUT'),
			(3549, '4', '02', '02', '04', '001', 'SALURAN INDUK PEMBUANG (SALURAN PEMBUANG PASANG SURUT)'),
			(3550, '4', '02', '02', '04', '002', 'SALURAN SEKUNDER PEMBUANG (SALURAN PEMBUANG PASANG SURUT)'),
			(3551, '4', '02', '02', '04', '003', 'SALURAN TERSIER PEMBUANG (SALURAN PEMBUANG PASANG SURUT)'),
			(3552, '4', '02', '02', '04', '004', 'SALURAN PENGUMPUL AIR'),
			(3553, '4', '02', '02', '04', '999', 'SALURAN PEMBUANG PASANG SURUT LAINNYA'),
			(3554, '4', '02', '02', '05', '000', 'BANGUNAN PENGAMAN PASANG SURUT'),
			(3555, '4', '02', '02', '05', '001', 'BANGUNAN PINTU AIR/KLEP (BANGUNAN PENGAMAN PASANG SURUT)'),
			(3556, '4', '02', '02', '05', '002', 'BANGUNAN PEMASUKAN/PEMBUANG'),
			(3557, '4', '02', '02', '05', '003', 'KOLAM PASANG'),
			(3558, '4', '02', '02', '05', '999', 'BANGUNAN PENGAMAN PASANG SURUT LAINNYA'),
			(3559, '4', '02', '02', '06', '000', 'BANGUNAN PELENGKAP PASANG SURUT'),
			(3560, '4', '02', '02', '06', '001', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP PASANG SURUT)'),
			(3561, '4', '02', '02', '06', '002', 'BANGUNAN JEMBATAN PENGHALANG (BANGUNAN PELENGKAP PASANG SURUT)'),
			(3562, '4', '02', '02', '06', '003', 'BANGUNAN PENUTUP PENANGKIS KOTORAN'),
			(3563, '4', '02', '02', '06', '004', 'BANGUNAN PENGUKUR MUKA AIR (BANGUNAN PELENGKAP PASANG SURUT)'),
			(3564, '4', '02', '02', '06', '005', 'BANGUNAN PENGUKUR CURAH HUJAN (BANGUNAN PELENGKAP PASANG SURUT)'),
			(3565, '4', '02', '02', '06', '999', 'BANGUNAN PELENGKAP PASANG SURUT LAINNYA'),
			(3566, '4', '02', '02', '07', '000', 'BANGUNAN SAWAH PASANG SURUT'),
			(3567, '4', '02', '02', '07', '001', 'BANGUNAN SAWAH PASANG SURUT TEKNIS'),
			(3568, '4', '02', '02', '07', '002', 'BANGUNAN SAWAH PASANG SURUT SEMI TEKNIS'),
			(3569, '4', '02', '02', '07', '003', 'BANGUNAN SAWAH PASANG SURUT NON TEKNIS'),
			(3570, '4', '02', '02', '07', '999', 'BANGUNAN SAWAH PASANG SURUT LAINNYA'),
			(3571, '4', '02', '02', '99', '000', 'BANGUNAN PENGAIRAN PASANG SURUT LAINNYA'),
			(3572, '4', '02', '02', '99', '999', 'BANGUNAN PENGAIRAN PASANG SURUT LAINNYA'),
			(3573, '4', '02', '03', '00', '000', 'BANGUNAN PENGEMBANGAN RAWA DAN POLDER'),
			(3574, '4', '02', '03', '01', '000', 'BANGUNAN WADUK PENGEMBANGAN RAWA'),
			(3575, '4', '02', '03', '01', '001', 'BANGUNAN WADUK'),
			(3576, '4', '02', '03', '01', '999', 'BANGUNAN WADUK PENGEMBANGAN RAWA LAINNYA'),
			(3577, '4', '02', '03', '02', '000', 'BANGUNAN PENGAMBILAN PENGEMBANGAN RAWA'),
			(3578, '4', '02', '03', '02', '001', 'WADUK PENGAMBILAN RAWA'),
			(3579, '4', '02', '03', '02', '999', 'BANGUNAN PENGAMBILAN PENGEMBANGAN RAWA LAINNYA'),
			(3580, '4', '02', '03', '03', '000', 'BANGUNAN PEMBAWA PENGEMBANGAN RAWA'),
			(3581, '4', '02', '03', '03', '001', 'SALURAN MUKA (BANGUNAN PEMBAWA PENGEMBANGAN RAWA)'),
			(3582, '4', '02', '03', '03', '002', 'SALURAN INDUK (BANGUNAN PEMBAWA PENGEMBANGAN RAWA)'),
			(3583, '4', '02', '03', '03', '003', 'SALURAN SEKUNDER (BANGUNAN PEMBAWA PENGEMBANGAN RAWA)'),
			(3584, '4', '02', '03', '03', '004', 'SALURAN TERSIER (BANGUNAN PEMBAWA PENGEMBANGAN RAWA)'),
			(3585, '4', '02', '03', '03', '999', 'BANGUNAN PEMBAWA PENGEMBANGAN RAWA LAINNYA'),
			(3586, '4', '02', '03', '04', '000', 'BANGUNAN PEMBUANG PENGEMBANGAN RAWA'),
			(3587, '4', '02', '03', '04', '001', 'SALURAN INDUK PEMBUANG (BANGUNAN PEMBUANG PENGEMBANGAN RAWA)'),
			(3588, '4', '02', '03', '04', '002', 'SALURAN SEKUNDER PEMBUANG (BANGUNAN PEMBUANG PENGEMBANGAN RAWA)'),
			(3589, '4', '02', '03', '04', '003', 'SALURAN TERSIER PEMBUANG (BANGUNAN PEMBUANG PENGEMBANGAN RAWA)'),
			(3590, '4', '02', '03', '04', '999', 'BANGUNAN PEMBUANG PENGEMBANGAN RAWA LAINNYA'),
			(3591, '4', '02', '03', '05', '000', 'BANGUNAN PENGAMAN PENGEMBANGAN RAWA'),
			(3592, '4', '02', '03', '05', '001', 'TANGGUL KELILING'),
			(3593, '4', '02', '03', '05', '002', 'BANGUNAN PINTU AIR/KLEP (BANGUNAN PENGAMAN PENGEMBANGAN RAWA)'),
			(3594, '4', '02', '03', '05', '999', 'BANGUNAN PENGAMAN PENGEMBANGAN RAWA LAINNYA'),
			(3595, '4', '02', '03', '06', '000', 'BANGUNAN PELENGKAP PENGEMBANGAN RAWA'),
			(3596, '4', '02', '03', '06', '001', 'BANGUNAN BAGI DAN SADAP (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3597, '4', '02', '03', '06', '002', 'BANGUNAN SADAP (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3598, '4', '02', '03', '06', '003', 'BANGUNAN TERJUN (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3599, '4', '02', '03', '06', '004', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3600, '4', '02', '03', '06', '005', 'BANGUNAN GORONG-GORONG (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3601, '4', '02', '03', '06', '006', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3602, '4', '02', '03', '06', '007', 'BANGUNAN JEMBATAN PENGHALANG (BGNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3603, '4', '02', '03', '06', '008', 'BANGUNAN PENGUKUR MUKA AIR (BANGUNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3604, '4', '02', '03', '06', '009', 'BANGUNAN PENGUKUR CURAH HUJAN (BGNAN PELENGKAP PENGEMBANGAN RAWA)'),
			(3605, '4', '02', '03', '06', '010', 'BANGUNAN PENUTUP SUNGAI'),
			(3606, '4', '02', '03', '06', '011', 'BANGUNAN STASIUN POMPA PEMASUKAN/PEMBUANG'),
			(3607, '4', '02', '03', '06', '999', 'BANGUNAN PELENGKAP PENGEMBANGAN RAWA LAINNYA'),
			(3608, '4', '02', '03', '07', '000', 'BANGUNAN SAWAH PENGEMBANGAN RAWA'),
			(3609, '4', '02', '03', '07', '001', 'BANGUNAN SAWAH RAWA TEKNIS'),
			(3610, '4', '02', '03', '07', '002', 'BANGUNAN SAWAH RAWA SEMI TEKNIS'),
			(3611, '4', '02', '03', '07', '003', 'BANGUNAN SAWAH RAWA NON TEKNIS'),
			(3612, '4', '02', '03', '07', '999', 'BANGUNAN SAWAH PENGEMBANGAN RAWA LAINNYA'),
			(3613, '4', '02', '03', '99', '000', 'BANGUNAN PENGEMBANGAN RAWA DAN POLDER LAINNYA'),
			(3614, '4', '02', '03', '99', '999', 'BANGUNAN PENGEMBANGAN RAWA DAN POLDER LAINNYA'),
			(3615, '4', '02', '04', '00', '000', 'BANGUNAN PENGAMAN SUNGAI/PANTAI & PENANGGULANGAN BENCANA ALAM'),
			(3616, '4', '02', '04', '01', '000', 'BANGUNAN PENGAMAN SUNGAI/PANTAI & PENANGGULANGAN BENCANA ALAM'),
			(3617, '4', '02', '04', '01', '001', 'BANGUNA WASUK PENGAMAN SUNGAI/PANTAI'),
			(3618, '4', '02', '04', '01', '002', '\"WADUK DENGAN TANGGUL'),
			(3619, '4', '02', '04', '01', '999', 'BANGUNAN PENGAMAN SUNGAI/PANTAI & PENANGGULANGAN BENCANA ALAM LAINNYA'),
			(3620, '4', '02', '04', '02', '000', 'BANGUNAN PENGAMBILAN PENGAMAN SUNGAI/PANTAI'),
			(3621, '4', '02', '04', '02', '001', 'BANGUNAN PENGAMBILAN PENGAMANAN SUNGAI'),
			(3622, '4', '02', '04', '02', '002', 'BANGUNAN PENGAMBILAN PENGAMANAN PANTAI'),
			(3623, '4', '02', '04', '02', '999', 'BANGUNAN PENGAMBILAN PENGAMAN SUNGAI/PANTAI LAINNYA'),
			(3624, '4', '02', '04', '03', '000', 'BANGUNAN PEMBAWA PENGAMAN SUNGAI/PANTAI'),
			(3625, '4', '02', '04', '03', '001', 'BANGUNAN PEMBAWA PENGAMAN SUNGAI'),
			(3626, '4', '02', '04', '03', '002', 'BANGUNAN PEMBAWA PENGAMAN PANTAI'),
			(3627, '4', '02', '04', '03', '999', 'BANGUNAN PEMBAWA PENGAMAN SUNGAI/PANTAI LAINNYA'),
			(3628, '4', '02', '04', '04', '000', 'BANGUNAN PEMBUANG PENGAMAN SUNGAI'),
			(3629, '4', '02', '04', '04', '001', 'SALURAN BANJIR'),
			(3630, '4', '02', '04', '04', '002', 'SALURAN DRAINAGE'),
			(3631, '4', '02', '04', '04', '999', 'BANGUNAN PEMBUANG PENGAMAN SUNGAI LAINNYA'),
			(3632, '4', '02', '04', '05', '000', 'BANGUNAN PENGAMAN PENGAMANAN SUNGAI/PANTAI'),
			(3633, '4', '02', '04', '05', '001', 'TANGGUL BANJIR (BANGUNAN PENGAMAN PENGAMANAN SUNGAI/PANTAI)'),
			(3634, '4', '02', '04', '05', '002', 'PINTU PENGATUR BANJIR'),
			(3635, '4', '02', '04', '05', '003', 'COUPURE/SODETAN'),
			(3636, '4', '02', '04', '05', '004', 'KANTONG PASIR/LAHAR/LUMPUR'),
			(3637, '4', '02', '04', '05', '005', 'CHEKDAM/PENAHAN SEDIMEN'),
			(3638, '4', '02', '04', '05', '006', 'KRIB PENGAMAN SUNGAI/PANTAI'),
			(3639, '4', '02', '04', '05', '007', 'BANGUNAN PENGUAT TEBING/PANTAI'),
			(3640, '4', '02', '04', '05', '008', 'BANGUNAN PELIMPAH BANJIR'),
			(3641, '4', '02', '04', '05', '009', 'DAM KONSOLIDASI'),
			(3642, '4', '02', '04', '05', '010', 'PERALATAN SARINGAN SAMPAH ( POND SCREEN )'),
			(3643, '4', '02', '04', '05', '011', 'KLEP PENGATUR BANJIR'),
			(3644, '4', '02', '04', '05', '012', 'BANGUNAN PEMECAH GELOMBANG'),
			(3645, '4', '02', '04', '05', '013', 'BANGUNAN PELANTARAN PANTAI'),
			(3646, '4', '02', '04', '05', '999', 'BANGUNAN PENGAMAN PENGAMANAN SUNGAI/PANTAI LAINNYA'),
			(3647, '4', '02', '04', '06', '000', 'BANGUNAN PELENGKAP PENGAMAN SUNGAI'),
			(3648, '4', '02', '04', '06', '001', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP PENGAMAN SUNGAI)'),
			(3649, '4', '02', '04', '06', '002', 'BANGUNAN GORONG-GORONG (BANGUNAN PELENGKAP PENGAMAN SUNGAI)'),
			(3650, '4', '02', '04', '06', '003', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP PENGAMAN SUNGAI)'),
			(3651, '4', '02', '04', '06', '004', 'BANGUNAN PENGUKUR MUKA AIR (BANGUNAN PELENGKAP PENGAMAN SUNGAI)'),
			(3652, '4', '02', '04', '06', '005', 'BANGUNAN PENGUKUR CURAH HUJAN (BGNAN PELENGKAP PENGAMAN SUNGAI)'),
			(3653, '4', '02', '04', '06', '006', 'STASIUN POS PENJAGA/PENGAMAT'),
			(3654, '4', '02', '04', '06', '007', 'BANGUNAN DERMAGA'),
			(3655, '4', '02', '04', '06', '008', 'BANGUNAN STASIUN POMPA PEMBUANG'),
			(3656, '4', '02', '04', '06', '009', 'WARNING SYSTEM'),
			(3657, '4', '02', '04', '06', '999', 'BANGUNAN PELENGKAP PENGAMAN SUNGAI LAINNYA'),
			(3658, '4', '02', '04', '99', '000', 'BANGUNAN PENGAMAN SUNGAI/PANTAI & PENANGGULANGAN BENCANA ALAM LAINNYA'),
			(3659, '4', '02', '04', '99', '999', 'BANGUNAN PENGAMAN SUNGAI/PANTAI & PENANGGULANGAN BENCANA ALAM LAINNYA'),
			(3660, '4', '02', '05', '00', '000', 'BANGUNAN PENGEMBANGAN SUMBER AIR DAN AIR TANAH'),
			(3661, '4', '02', '05', '01', '000', 'BANGUNAN WADUK PENGEMBANGAN SUMBER AIR'),
			(3662, '4', '02', '05', '01', '001', 'EMBUNG/WADUK LAPANGAN'),
			(3663, '4', '02', '05', '01', '999', 'BANGUNAN WADUK PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3664, '4', '02', '05', '02', '000', 'BANGUNAN PENGAMBILAN PENGEMBANGAN SUMBER AIR'),
			(3665, '4', '02', '05', '02', '001', 'SUMUR DENGAN POMPA (BANGUNAN PENGAMBILAN PENGEMBANGAN SUMBER AIR)'),
			(3666, '4', '02', '05', '02', '002', 'SUMUR ARTETIS'),
			(3667, '4', '02', '05', '02', '999', 'BANGUNAN PENGAMBILAN PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3668, '4', '02', '05', '03', '000', 'BANGUNAN PEMBAWA PENGEMBANGAN SUMBER AIR'),
			(3669, '4', '02', '05', '03', '001', 'SALURAN TERSIER (BANGUNAN PEMBAWA PENGEMBANGAN SUMBER AIR)'),
			(3670, '4', '02', '05', '03', '002', 'SALURAN KUARTIER'),
			(3671, '4', '02', '05', '03', '999', 'BANGUNAN PEMBAWA PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3672, '4', '02', '05', '04', '000', 'BANGUNAN PEMBUANG PENGEMBANGAN SUMBER AIR'),
			(3673, '4', '02', '05', '04', '001', 'SALURAN PEMBUANG'),
			(3674, '4', '02', '05', '04', '999', 'BANGUNAN PEMBUANG PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3675, '4', '02', '05', '05', '000', 'BANGUNAN PENGAMAN PENGEMBANGAN SUMBER AIR'),
			(3676, '4', '02', '05', '05', '001', 'BAK PENAMPUNG/KOLAM/ MENARA PENAMPUNGAN'),
			(3677, '4', '02', '05', '05', '002', 'BANGUNAN KLIMATOLOGI'),
			(3678, '4', '02', '05', '05', '003', 'BANGUNAN HIDROMETRI'),
			(3679, '4', '02', '05', '05', '004', 'SUMUR PENGAMATAN'),
			(3680, '4', '02', '05', '05', '999', 'BANGUNAN PENGAMAN PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3681, '4', '02', '05', '06', '000', 'BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR'),
			(3682, '4', '02', '05', '06', '001', 'BANGUNAN TERJUN (BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3683, '4', '02', '05', '06', '002', 'BANGUNAN TALANG (BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3684, '4', '02', '05', '06', '003', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3685, '4', '02', '05', '06', '004', 'BANGUNAN GORONG-GORONG (BGNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3686, '4', '02', '05', '06', '005', 'BANGUNAN BOX TERSIER (BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3687, '4', '02', '05', '06', '006', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR)'),
			(3688, '4', '02', '05', '06', '999', 'BANGUNAN PELENGKAP PENGEMBANGAN SUMBER AIR LAINNYA'),
			(3689, '4', '02', '05', '07', '000', 'BANGUNAN SAWAH IRIGASI AIR TANAH'),
			(3690, '4', '02', '05', '07', '001', 'BANGUNAN SAWAH IRIGASI AIR TANAH TEKNIS'),
			(3691, '4', '02', '05', '07', '002', 'BANGUNAN SAWAH IRIGASI AIR TANAH SEMI TEKNIS'),
			(3692, '4', '02', '05', '07', '003', 'BANGUNAN SAWAH IRIGASI AIR TANAH NON TEKNIS'),
			(3693, '4', '02', '05', '07', '999', 'BANGUNAN SAWAH IRIGASI AIR TANAH LAINNYA'),
			(3694, '4', '02', '05', '99', '000', 'BANGUNAN PENGEMBANGAN SUMBER AIR DAN AIR TANAH LAINNYA'),
			(3695, '4', '02', '05', '99', '999', 'BANGUNAN PENGEMBANGAN SUMBER AIR DAN AIR TANAH LAINNYA'),
			(3696, '4', '02', '06', '00', '000', 'BANGUNAN AIR BERSIH/AIR BAKU'),
			(3697, '4', '02', '06', '01', '000', 'BANGUNAN WADUK AIR BERSIH/AIR BAKU'),
			(3698, '4', '02', '06', '01', '001', 'WADUK PENYIMPANAN AIR BAKU'),
			(3699, '4', '02', '06', '01', '002', 'WADUK PENYIMPANAN AIR HUJAN'),
			(3700, '4', '02', '06', '01', '003', 'BAK PENYIMPANAN/TOWER AIR BAKU'),
			(3701, '4', '02', '06', '01', '999', 'BANGUNAN WADUK AIR BERSIH/AIR BAKU LAINNYA'),
			(3702, '4', '02', '06', '02', '000', 'BANGUNAN PENGAMBILAN AIR BERSIH/AIR BAKU'),
			(3703, '4', '02', '06', '02', '001', 'BANGUNAN PENGAMBILAN DARI WADUK'),
			(3704, '4', '02', '06', '02', '002', 'BANGUNAN PENGAMBILAN DARI SUNGAI'),
			(3705, '4', '02', '06', '02', '003', 'BANGUNAN PENGAMBILAN DARI DANAU'),
			(3706, '4', '02', '06', '02', '004', 'BANGUNAN PENGAMBILAN DARI RAWA'),
			(3707, '4', '02', '06', '02', '005', 'BANGUNAN PENGAMBILAN DARI AIR LAUT'),
			(3708, '4', '02', '06', '02', '006', 'BANGUNAN PENGAMBILAN DARI SUMBER AIR'),
			(3709, '4', '02', '06', '02', '007', 'BANGUNAN PENGAMBILAN DARI SUMUR ARTETIS'),
			(3710, '4', '02', '06', '02', '999', 'BANGUNAN PENGAMBILAN AIR BERSIH/AIR BAKU LAINNYA'),
			(3711, '4', '02', '06', '03', '000', 'BANGUNAN PEMBAWA AIR BERSIH/AIR BAKU'),
			(3712, '4', '02', '06', '03', '001', 'SALURAN PEMBAWA AIR BAKU TERBUKA'),
			(3713, '4', '02', '06', '03', '002', 'SALURAN PEMBAWA AIR BAKU TERTUTUP'),
			(3714, '4', '02', '06', '03', '999', 'BANGUNAN PEMBAWA AIR BERSIH/AIR BAKU LAINNYA'),
			(3715, '4', '02', '06', '04', '000', 'BANGUNAN PEMBUANG AIR BERSIH/AIR BAKU'),
			(3716, '4', '02', '06', '04', '001', 'SALURAN PEMBUANG AIR CUCIAN AIR BAKU'),
			(3717, '4', '02', '06', '04', '002', 'SALURAN PEMBUANG AIR CUCIAN INSTALASI'),
			(3718, '4', '02', '06', '04', '999', 'BANGUNAN PEMBUANG AIR BERSIH/AIR BAKU LAINNYA'),
			(3719, '4', '02', '06', '05', '000', 'BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU'),
			(3720, '4', '02', '06', '05', '001', 'BANGUNAN TALANG (BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU)'),
			(3721, '4', '02', '06', '05', '002', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU)'),
			(3722, '4', '02', '06', '05', '003', 'BANGUNAN GORONG-GORONG (BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU)'),
			(3723, '4', '02', '06', '05', '004', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU)'),
			(3724, '4', '02', '06', '05', '005', 'BANGUNAN PENAMPUNG AIR BAKU'),
			(3725, '4', '02', '06', '05', '006', 'BANGUNAN HIDRAN UMUM'),
			(3726, '4', '02', '06', '05', '007', 'BANGUNAN MANDI CUCI KAKUS (MCK)'),
			(3727, '4', '02', '06', '05', '008', 'BANGUNAN MENARA/BAK PENAMPUNG/RESERVOIR AIR MINUM'),
			(3728, '4', '02', '06', '05', '009', 'BANGUNAN BUSTER PUMP'),
			(3729, '4', '02', '06', '05', '999', 'BANGUNAN PELENGKAP AIR BERSIH/AIR BAKU LAINNYA'),
			(3730, '4', '02', '06', '99', '000', 'BANGUNAN AIR BERSIH/AIR BAKU LAINNYA'),
			(3731, '4', '02', '06', '99', '999', 'BANGUNAN AIR BERSIH/AIR BAKU LAINNYA'),
			(3732, '4', '02', '07', '00', '000', 'BANGUNAN AIR KOTOR'),
			(3733, '4', '02', '07', '01', '000', 'BANGUNAN PEMBAWA AIR KOTOR'),
			(3734, '4', '02', '07', '01', '001', 'SALURAN PENGUMPUL AIR HUJAN'),
			(3735, '4', '02', '07', '01', '002', 'SALURAN PENGUMPUL AIR BUANGAN DOMESTIK'),
			(3736, '4', '02', '07', '01', '003', 'SALURAN PENGUMPUL AIR BUANGAN INDUSTRI'),
			(3737, '4', '02', '07', '01', '004', 'SALURAN PENGUMPUL AIR BUANGAN PERTANIAN'),
			(3738, '4', '02', '07', '01', '999', 'BANGUNAN PEMBAWA AIR KOTOR LAINNYA'),
			(3739, '4', '02', '07', '02', '000', 'BANGUNAN WADUK AIR KOTOR'),
			(3740, '4', '02', '07', '02', '001', 'WADUK AIR HUJAN'),
			(3741, '4', '02', '07', '02', '002', 'WADUK AIR BUANGAN DOMESTIK'),
			(3742, '4', '02', '07', '02', '003', 'WADUK AIR BUANGAN INDUSTRI'),
			(3743, '4', '02', '07', '02', '004', 'WADUK AIR BUANGAN PERTANIAN'),
			(3744, '4', '02', '07', '02', '999', 'BANGUNAN WADUK AIR KOTOR LAINNYA'),
			(3745, '4', '02', '07', '03', '000', 'BANGUNAN PEMBUANG AIR KOTOR'),
			(3746, '4', '02', '07', '03', '001', 'SALURAN PEMBUANG AIR BUANGAN AIR HUJAN'),
			(3747, '4', '02', '07', '03', '002', 'SALURAN PEMBUANG AIR BUANGAN DOMESTIK'),
			(3748, '4', '02', '07', '03', '003', 'SALURAN PEMBUANG AIR BUANGAN AIR INDUSTRI'),
			(3749, '4', '02', '07', '03', '004', 'SALURAN PEMBUANG AIR BUANGAN AIR PERTANIAN'),
			(3750, '4', '02', '07', '03', '999', 'BANGUNAN PEMBUANG AIR KOTOR LAINNYA'),
			(3751, '4', '02', '07', '04', '000', 'BANGUNAN PENGAMAN AIR KOTOR'),
			(3752, '4', '02', '07', '04', '001', 'BANGUNAN POMPA AIR HUJAN'),
			(3753, '4', '02', '07', '04', '002', 'BANGUNAN POMPA AIR BUANGAN DOMESTIK'),
			(3754, '4', '02', '07', '04', '003', 'BANGUNAN POMPA AIR BUANGAN INDUSTRI'),
			(3755, '4', '02', '07', '04', '004', 'BANGUNAN POMPA AIR BUANGAN PERTANIAN'),
			(3756, '4', '02', '07', '04', '999', 'BANGUNAN PENGAMAN AIR KOTOR LAINNYA'),
			(3757, '4', '02', '07', '05', '000', 'BANGUNAN PELENGKAP AIR KOTOR'),
			(3758, '4', '02', '07', '05', '001', 'BANGUNAN TALANG (BANGUNAN PELENGKAP AIR KOTOR)'),
			(3759, '4', '02', '07', '05', '002', 'BANGUNAN SYPHON (BANGUNAN PELENGKAP AIR KOTOR)'),
			(3760, '4', '02', '07', '05', '003', 'BANGUNAN GORONG-GORONG (BANGUNAN PELENGKAP AIR KOTOR)'),
			(3761, '4', '02', '07', '05', '004', 'BANGUNAN JEMBATAN (BANGUNAN PELENGKAP AIR KOTOR)'),
			(3762, '4', '02', '07', '05', '005', 'BANGUNAN BAK KONTROL/MAN HOLE'),
			(3763, '4', '02', '07', '05', '006', 'SALURAN AIR KOTOR SAMBUNGAN DARI RUMAH'),
			(3764, '4', '02', '07', '05', '007', 'BANGUNAN (BOX) CULVERT'),
			(3765, '4', '02', '07', '05', '008', 'MULTIPLE PIPA ARCHES'),
			(3766, '4', '02', '07', '05', '009', 'BANGUNAN PLAT DEKER'),
			(3767, '4', '02', '07', '05', '999', 'BANGUNAN PELENGKAP AIR KOTOR LAINNYA'),
			(3768, '4', '02', '07', '99', '000', 'BANGUNAN AIR KOTOR LAINNYA'),
			(3769, '4', '02', '07', '99', '999', 'BANGUNAN AIR KOTOR LAINNYA'),
			(3770, '4', '03', '00', '00', '000', 'INSTALASI'),
			(3771, '4', '03', '01', '00', '000', 'INSTALASI AIR BERSIH / AIR BAKU'),
			(3772, '4', '03', '01', '01', '000', 'INSTALASI AIR PERMUKAAN'),
			(3773, '4', '03', '01', '01', '001', 'INSTALASI AIR PERMUKAAN KAPASITAS KECIL'),
			(3774, '4', '03', '01', '01', '002', 'INSTALASI AIR PERMUKAAN KAPASITAS SEDANG'),
			(3775, '4', '03', '01', '01', '003', 'INSTALASI AIR PERMUKAAN KAPASITAS BESAR'),
			(3776, '4', '03', '01', '01', '999', 'INSTALASI AIR PERMUKAAN LAINNYA'),
			(3777, '4', '03', '01', '02', '000', 'INSTALASI AIR SUMBER / MATA AIR'),
			(3778, '4', '03', '01', '02', '001', 'INSTALASI AIR SUMBER / MATA AIR KAPASITAS KECIL'),
			(3779, '4', '03', '01', '02', '002', 'INSTALASI AIR SUMBER / MATA AIR KAPASITAS SEDANG'),
			(3780, '4', '03', '01', '02', '003', 'INSTALASI AIR SUMBER / MATA AIR KAPASITAS BESAR'),
			(3781, '4', '03', '01', '02', '999', 'INSTALASI AIR SUMBER / MATA AIR LAINNYA'),
			(3782, '4', '03', '01', '03', '000', 'INSTALASI AIR TANAH DALAM'),
			(3783, '4', '03', '01', '03', '001', 'INSTALASI AIR TANAH DALAM KAPASITAS KECIL'),
			(3784, '4', '03', '01', '03', '002', 'INSTALASI AIR TANAH DALAM KAPASITAS SEDANG'),
			(3785, '4', '03', '01', '03', '003', 'INSTALASI AIR TANAH DALAM KAPASITAS BESAR'),
			(3786, '4', '03', '01', '03', '999', 'INSTALASI AIR TANAH DALAM LAINNYA'),
			(3787, '4', '03', '01', '04', '000', 'INSTALASI AIR TANAH DANGKAL'),
			(3788, '4', '03', '01', '04', '001', 'INSTALASI AIR TANAH DANGKAL KAPASITAS KECIL'),
			(3789, '4', '03', '01', '04', '002', 'INSTALASI AIR TANAH DANGKAL KAPASITAS SEDANG'),
			(3790, '4', '03', '01', '04', '003', 'INSTALASI AIR TANAH DANGKAL KAPASITAS BESAR'),
			(3791, '4', '03', '01', '04', '999', 'INSTALASI AIR TANAH DANGKAL LAINNYA'),
			(3792, '4', '03', '01', '05', '000', 'INSTALASI AIR BERSIH / AIR BAKU LAINNYA'),
			(3793, '4', '03', '01', '05', '001', 'SISTEM PENGOLAHAN AIR SEDERHANA (SIPAS)'),
			(3794, '4', '03', '01', '05', '002', 'JARINGAN RUMAH TANGGA (JARUT)'),
			(3795, '4', '03', '01', '05', '003', 'PENAMPUNGAN AIR HUJAN (PAH)'),
			(3796, '4', '03', '01', '05', '004', 'SUMUR GALI (SGL)'),
			(3797, '4', '03', '01', '05', '005', 'SUMUR RESAPAN'),
			(3798, '4', '03', '01', '05', '999', 'INSTALASI AIR BERSIH / AIR BAKU LAINNYA LAINNYA'),
			(3799, '4', '03', '01', '99', '000', 'INSTALASI AIR BERSIH / AIR BAKU LAINNYA'),
			(3800, '4', '03', '01', '99', '999', 'INSTALASI AIR BERSIH / AIR BAKU LAINNYA'),
			(3801, '4', '03', '02', '00', '000', 'INSTALASI AIR KOTOR'),
			(3802, '4', '03', '02', '01', '000', 'INSTALASI AIR BUANGAN DOMESTIK'),
			(3803, '4', '03', '02', '01', '001', 'INSTALASI AIR BUANGAN DOMESTIK KAPASITAS KECIL'),
			(3804, '4', '03', '02', '01', '002', 'INSTALASI AIR BUANGAN DOMESTIK KAPASITAS SEDANG'),
			(3805, '4', '03', '02', '01', '003', 'INSTALASI AIR BUANGAN DOMESTIK KAPASITAS BESAR'),
			(3806, '4', '03', '02', '01', '999', 'INSTALASI AIR BUANGAN DOMESTIK LAINNYA'),
			(3807, '4', '03', '02', '02', '000', 'INSTALASI AIR BUANGAN INDUSTRI'),
			(3808, '4', '03', '02', '02', '001', 'INSTALASI AIR BUANGAN INDUSTRI KAPASITAS KECIL'),
			(3809, '4', '03', '02', '02', '002', 'INSTALASI AIR BUANGAN INDUSTRI KAPASITAS SEDANG'),
			(3810, '4', '03', '02', '02', '003', 'INSTALASI AIR BUANGAN INDUSTRI KAPASITAS BESAR'),
			(3811, '4', '03', '02', '02', '999', 'INSTALASI AIR BUANGAN INDUSTRI LAINNYA'),
			(3812, '4', '03', '02', '03', '000', 'INSTALASI AIR BUANGAN PERTANIAN'),
			(3813, '4', '03', '02', '03', '001', 'INSTALASI AIR BUANGAN PERTANIAN KAPASITAS KECIL'),
			(3814, '4', '03', '02', '03', '002', 'INSTALASI AIR BUANGAN PERTANIAN KAPASITAS SEDANG'),
			(3815, '4', '03', '02', '03', '003', 'INSTALASI AIR BUANGAN PERTANIAN KAPASITAS BESAR'),
			(3816, '4', '03', '02', '03', '999', 'INSTALASI AIR BUANGAN PERTANIAN LAINNYA'),
			(3817, '4', '03', '02', '99', '000', 'INSTALASI AIR KOTOR LAINNYA'),
			(3818, '4', '03', '02', '99', '999', 'INSTALASI AIR KOTOR LAINNYA'),
			(3819, '4', '03', '03', '00', '000', 'INSTALASI PENGOLAHAN SAMPAH'),
			(3820, '4', '03', '03', '01', '000', 'INSTALASI PENGOLAHAN SAMPAH ORGANIK'),
			(3821, '4', '03', '03', '01', '001', 'INSTALASI PENGOLAHAN SAMPAH ORGANIK SISTEM PEMBAKARAN'),
			(3822, '4', '03', '03', '01', '002', 'INSTALASI PENGOLAHAN SAMPAH ORGANIK SISTEM KOMPOS'),
			(3823, '4', '03', '03', '01', '003', 'INSTALASI PENGOLAHAN SAMPAH ORGANIK SISTEM PENIMBUNAN'),
			(3824, '4', '03', '03', '01', '999', 'INSTALASI PENGOLAHAN SAMPAH ORGANIK LAINNYA'),
			(3825, '4', '03', '03', '02', '000', 'INSTALASI PENGOLAHAN SAMPAH NON ORGANIK'),
			(3826, '4', '03', '03', '02', '001', 'INSTALASI PENGOLAHAN SAMPAH NON ORGANIK DAUR ULANG LOGAM'),
			(3827, '4', '03', '03', '02', '002', 'INSTALASI PENGOLAHAN SAMPAH NON ORGANIK DAUR ULANG NON LOGAM'),
			(3828, '4', '03', '03', '02', '999', 'INSTALASI PENGOLAHAN SAMPAH NON ORGANIK LAINNYA'),
			(3829, '4', '03', '03', '03', '000', 'BANGUNAN PENAMPUNG SAMPAH'),
			(3830, '4', '03', '03', '03', '001', 'BANGUNAN TEMPAT PENAMPUNG SAMPAH RUMAH TANGGA'),
			(3831, '4', '03', '03', '03', '002', 'BANGUNAN TEMPAT MENAMPUNG SAMPAH LINGKUNGAN'),
			(3832, '4', '03', '03', '03', '999', 'BANGUNAN PENAMPUNG SAMPAH LAINNYA'),
			(3833, '4', '03', '03', '99', '000', 'INSTALASI PENGOLAHAN SAMPAH LAINNYA'),
			(3834, '4', '03', '03', '99', '999', 'INSTALASI PENGOLAHAN SAMPAH LAINNYA'),
			(3835, '4', '03', '04', '00', '000', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN'),
			(3836, '4', '03', '04', '01', '000', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN'),
			(3837, '4', '03', '04', '01', '001', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PENGAWETAN KAYU'),
			(3838, '4', '03', '04', '01', '002', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PENGERINGAN KAYU'),
			(3839, '4', '03', '04', '01', '003', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PENGERJAAN KAYU'),
			(3840, '4', '03', '04', '01', '004', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PERKAPURAN'),
			(3841, '4', '03', '04', '01', '005', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PEMBUATAN BATU'),
			(3842, '4', '03', '04', '01', '006', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN PEMBUATAN AGREGA'),
			(3843, '4', '03', '04', '01', '999', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERCONTOHAN LAINNYA'),
			(3844, '4', '03', '04', '02', '000', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS'),
			(3845, '4', '03', '04', '02', '001', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PENGAWETAN KAYU'),
			(3846, '4', '03', '04', '02', '002', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PENGERINGAN KAYU'),
			(3847, '4', '03', '04', '02', '003', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PENGERJAAN KAYU'),
			(3848, '4', '03', '04', '02', '004', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PERKAPURAN'),
			(3849, '4', '03', '04', '02', '005', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PEMBUATAN BATU CETA'),
			(3850, '4', '03', '04', '02', '006', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS PEMBUATAN AGREGAT'),
			(3851, '4', '03', '04', '02', '999', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN PERINTIS LAINNYA'),
			(3852, '4', '03', '04', '03', '000', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN TERAPAN'),
			(3853, '4', '03', '04', '03', '001', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN TERAPAN'),
			(3854, '4', '03', '04', '03', '999', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN TERAPAN LAINNYA'),
			(3855, '4', '03', '04', '99', '000', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN LAINNYA'),
			(3856, '4', '03', '04', '99', '999', 'INSTALASI PENGOLAHAN BAHAN BANGUNAN LAINNYA'),
			(3857, '4', '03', '05', '00', '000', 'INSTALASI PEMBANGKIT LISTRIK'),
			(3858, '4', '03', '05', '01', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA AIR (PLTA)'),
			(3859, '4', '03', '05', '01', '001', 'INSTALASI PLTA KAPASITAS KECIL'),
			(3860, '4', '03', '05', '01', '002', 'INSTALASI PLTA KAPASITAS SEDANG'),
			(3861, '4', '03', '05', '01', '003', 'INSTALASI PLTA KAPASITAS BESAR'),
			(3862, '4', '03', '05', '01', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA AIR (PLTA) LAINNYA'),
			(3863, '4', '03', '05', '02', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA DIESEL (PLTD)'),
			(3864, '4', '03', '05', '02', '001', 'INSTALASI PLTD KAPASITAS KECIL'),
			(3865, '4', '03', '05', '02', '002', 'INSTALASI PLTD KAPASITAS SEDANG'),
			(3866, '4', '03', '05', '02', '003', 'INSTALASI PLTD KAPASITAS BESAR'),
			(3867, '4', '03', '05', '02', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA DIESEL (PLTD) LAINNYA'),
			(3868, '4', '03', '05', '03', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA MIKRO HIDRO (PLTM)'),
			(3869, '4', '03', '05', '03', '001', 'INSTALASI PLTM KAPASITAS KECIL'),
			(3870, '4', '03', '05', '03', '002', 'INSTALASI PLTM KAPASITAS SEDANG'),
			(3871, '4', '03', '05', '03', '003', 'INSTALASI PLTM KAPASITAS BESAR'),
			(3872, '4', '03', '05', '03', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA MIKRO HIDRO (PLTM) LAINNYA'),
			(3873, '4', '03', '05', '04', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA ANGIN (PLTAN)'),
			(3874, '4', '03', '05', '04', '001', 'INSTALASI PLTAN KAPASITAS KECIL'),
			(3875, '4', '03', '05', '04', '002', 'INSTALASI PLTAN KAPASITAS SEDANG'),
			(3876, '4', '03', '05', '04', '003', 'INSTALASI PLTAN KAPASITAS BESAR'),
			(3877, '4', '03', '05', '04', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA ANGIN (PLTAN) LAINNYA'),
			(3878, '4', '03', '05', '05', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA UAP (PLTU)'),
			(3879, '4', '03', '05', '05', '001', 'INSTALASI PLTU KAPASITAS KECIL'),
			(3880, '4', '03', '05', '05', '002', 'INSTALASI PLTU KAPASITAS SEDANG'),
			(3881, '4', '03', '05', '05', '003', 'INSTALASI PLTU KAPASITAS BESAR'),
			(3882, '4', '03', '05', '05', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA UAP (PLTU) LAINNYA'),
			(3883, '4', '03', '05', '06', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA NUKLIR (PLTN)'),
			(3884, '4', '03', '05', '06', '001', 'INSTALASI PLTN KAPASITAS KECIL'),
			(3885, '4', '03', '05', '06', '002', 'INSTALASI PLTN KAPASITAS SEDANG'),
			(3886, '4', '03', '05', '06', '003', 'INSTALASI PLTN KAPASITAS BESAR'),
			(3887, '4', '03', '05', '06', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA NUKLIR (PLTN) LAINNYA'),
			(3888, '4', '03', '05', '07', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA GAS (PLTG)'),
			(3889, '4', '03', '05', '07', '001', 'INSTALASI PLTG KAPASITAS KECIL'),
			(3890, '4', '03', '05', '07', '002', 'INSTALASI PLTG KAPASITAS SEDANG'),
			(3891, '4', '03', '05', '07', '003', 'INSTALASI PLTG KAPASITAS BESAR'),
			(3892, '4', '03', '05', '07', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA GAS (PLTG) LAINNYA'),
			(3893, '4', '03', '05', '08', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA PANAS BUMI (PLTP)'),
			(3894, '4', '03', '05', '08', '001', 'INSTALASI PLTP KAPASITAS KECIL'),
			(3895, '4', '03', '05', '08', '002', 'INSTALASI PLTP KAPASITAS SEDANG'),
			(3896, '4', '03', '05', '08', '003', 'INSTALASI PLTP KAPASITAS BESAR'),
			(3897, '4', '03', '05', '08', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA PANAS BUMI (PLTP) LAINNYA'),
			(3898, '4', '03', '05', '09', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA SURYA (PLTS)'),
			(3899, '4', '03', '05', '09', '001', 'INSTALASI PLTS KAPASITAS KECIL'),
			(3900, '4', '03', '05', '09', '002', 'INSTALASI PLTS KAPASITAS SEDANG'),
			(3901, '4', '03', '05', '09', '003', 'INSTALASI PLTS KAPASITAS BESAR'),
			(3902, '4', '03', '05', '09', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA SURYA (PLTS) LAIINNYA'),
			(3903, '4', '03', '05', '10', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA BIOGAS (PLTB)'),
			(3904, '4', '03', '05', '10', '001', 'INSTALASI PLTB KAPASITAS KECIL'),
			(3905, '4', '03', '05', '10', '002', 'INSTALASI PLTB KAPASITAS SEDANG'),
			(3906, '4', '03', '05', '10', '003', 'INSTALASI PLTB KAPASITAS BESAR'),
			(3907, '4', '03', '05', '10', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA BIOGAS (PLTB) LAINNYA'),
			(3908, '4', '03', '05', '11', '000', 'INSTALASI PEMBANGKIT LISTRIK TENAGA SAMUDERA / GELOMBANG SAMUDERA'),
			(3909, '4', '03', '05', '11', '001', 'INSTALASI PLTSM KAPASITAS KECIL'),
			(3910, '4', '03', '05', '11', '002', 'INSTALASI PLTSM KAPASITAS SEDANG'),
			(3911, '4', '03', '05', '11', '003', 'INSTALASI PLTSM KAPASITAS BESAR'),
			(3912, '4', '03', '05', '11', '999', 'INSTALASI PEMBANGKIT LISTRIK TENAGA SAMUDERA / GELOMBANG SAMUDERA LAINNYA'),
			(3913, '4', '03', '05', '99', '000', 'INSTALASI PEMBANGKIT LISTRIK LAINNYA'),
			(3914, '4', '03', '05', '99', '999', 'INSTALASI PEMBANGKIT LISTRIK LAINNYA'),
			(3915, '4', '03', '06', '00', '000', 'INSTALASI GARDU LISTRIK'),
			(3916, '4', '03', '06', '01', '000', 'INSTALASI GARDU LISTRIK INDUK'),
			(3917, '4', '03', '06', '01', '001', 'INSTALASI GARDU LISTRIK INDUK KAPASITAS KECIL'),
			(3918, '4', '03', '06', '01', '002', 'INSTALASI GARDU LISTRIK INDUK KAPASITAS SEDANG'),
			(3919, '4', '03', '06', '01', '003', 'INSTALASI GARDU LISTRIK INDUK KAPASITAS BESAR'),
			(3920, '4', '03', '06', '01', '999', 'INSTALASI GARDU LISTRIK INDUK LAINNYA'),
			(3921, '4', '03', '06', '02', '000', 'INSTALASI GARDU LISTRIK DISTRIBUSI'),
			(3922, '4', '03', '06', '02', '001', 'INSTALASI GARDU LISTRIK DISTRIBUSI KAPASITAS KECIL'),
			(3923, '4', '03', '06', '02', '002', 'INSTALASI GARDU LISTRIK DISTRIBUSI KAPASITAS SEDANG'),
			(3924, '4', '03', '06', '02', '003', 'INSTALASI GARDU LISTRIK DISTRIBUSI KAPASITAS BESAR'),
			(3925, '4', '03', '06', '02', '999', 'INSTALASI GARDU LISTRIK DISTRIBUSI LAINNYA'),
			(3926, '4', '03', '06', '03', '000', 'INSTALASI PUSAT PENGATUR LISTRIK'),
			(3927, '4', '03', '06', '03', '001', 'INSTALASI PUSAT PENGATUR LISTRIK KAPASITAS KECIL'),
			(3928, '4', '03', '06', '03', '002', 'INSTALASI PUSAT PENGATUR LISTRIK KAPASITAS SEDANG'),
			(3929, '4', '03', '06', '03', '003', 'INSTALASI PUSAT PENGATUR LISTRIK KAPASITAS BESAR'),
			(3930, '4', '03', '06', '03', '999', 'INSTALASI PUSAT PENGATUR LISTRIK LAINNYA'),
			(3931, '4', '03', '06', '99', '000', 'INSTALASI GARDU LISTRIK LAINNYA'),
			(3932, '4', '03', '06', '99', '999', 'INSTALASI GARDU LISTRIK LAINNYA'),
			(3933, '4', '03', '07', '00', '000', 'INSTALASI LAIN'),
			(3934, '4', '03', '07', '01', '000', 'INSTALASI LAIN'),
			(3935, '4', '03', '07', '01', '001', 'INSTALASI GENERATING SET'),
			(3936, '4', '03', '07', '01', '002', 'INSTALASI AC'),
			(3937, '4', '03', '07', '01', '003', 'INSTALASI BUILDING AUTOMATION SYSTEM (BAS)'),
			(3938, '4', '03', '07', '01', '004', 'INSTALASI KOMPUTER'),
			(3939, '4', '03', '07', '01', '999', 'INSTALASI LAIN-LAIN'),
			(3940, '4', '04', '00', '00', '000', 'JARINGAN'),
			(3941, '4', '04', '01', '00', '000', 'JARINGAN AIR MINUM'),
			(3942, '4', '04', '01', '01', '000', 'JARINGAN PEMBAWA'),
			(3943, '4', '04', '01', '01', '001', 'JARINGAN PEMBAWA KAPASITAS KECIL'),
			(3944, '4', '04', '01', '01', '002', 'JARINGAN PEMBAWA KAPASITAS SEDANG'),
			(3945, '4', '04', '01', '01', '003', 'JARINGAN PEMBAWA KAPASITAS BESAR'),
			(3946, '4', '04', '01', '01', '999', 'JARINGAN PEMBAWA LAINNYA'),
			(3947, '4', '04', '01', '02', '000', 'JARINGAN INDUK DISTRIBUSI'),
			(3948, '4', '04', '01', '02', '001', 'JARINGAN INDUK DISTRIBUSI KAPASITAS KECIL'),
			(3949, '4', '04', '01', '02', '002', 'JARINGAN INDUK DISTRIBUSI KAPASITAS SEDANG'),
			(3950, '4', '04', '01', '02', '003', 'JARINGAN INDUK DISTRIBUSI KAPASITAS BESAR'),
			(3951, '4', '04', '01', '02', '999', 'JARINGAN INDUK DISTRIBUSI LAINNYA'),
			(3952, '4', '04', '01', '03', '000', 'JARINGAN CABANG DISTRIBUSI'),
			(3953, '4', '04', '01', '03', '001', 'JARINGAN CABANG DISTRIBUSI KAPASITAS KECIL'),
			(3954, '4', '04', '01', '03', '002', 'JARINGAN CABANG DISTRIBUSI KAPASITAS SEDANG'),
			(3955, '4', '04', '01', '03', '003', 'JARINGAN CABANG DISTRIBUSI KAPASITAS BESAR'),
			(3956, '4', '04', '01', '03', '999', 'JARINGAN CABANG DISTRIBUSI LAINNYA'),
			(3957, '4', '04', '01', '04', '000', 'JARINGAN SAMBUNGAN KE RUMAH'),
			(3958, '4', '04', '01', '04', '001', 'JARINGAN SAMBUNGAN KE RUMAH KAPASITAS KECIL'),
			(3959, '4', '04', '01', '04', '002', 'JARINGAN SAMBUNGAN KE RUMAH KAPASITAS SEDANG'),
			(3960, '4', '04', '01', '04', '003', 'JARINGAN SAMBUNGAN KE RUMAH KAPASITAS BESAR'),
			(3961, '4', '04', '01', '04', '999', 'JARINGAN SAMBUNGAN KE RUMAH LAINNYA'),
			(3962, '4', '04', '01', '99', '000', 'JARINGAN AIR MINUM LAINNYA'),
			(3963, '4', '04', '01', '99', '999', 'JARINGAN AIR MINUM LAINNYA'),
			(3964, '4', '04', '02', '00', '000', 'JARINGAN LISTRIK'),
			(3965, '4', '04', '02', '01', '000', 'JARINGAN TRANSMISI'),
			(3966, '4', '04', '02', '01', '001', 'JARINGAN TRANSMISI TEGANGAN DIATAS 300 KVA'),
			(3967, '4', '04', '02', '01', '002', 'JARINGAN TRANSMISI TEGANGAN 100 S/D 300 KVA'),
			(3968, '4', '04', '02', '01', '003', 'JARINGAN TRANSMISI TEGANGAN DIBAWAH 100 KVA'),
			(3969, '4', '04', '02', '01', '999', 'JARINGAN TRANSMISI LAINNYA'),
			(3970, '4', '04', '02', '02', '000', 'JARINGAN DISTRIBUSI'),
			(3971, '4', '04', '02', '02', '001', 'JARINGAN DISTRIBUSI TEGANGAN DIATAS 20 KVA'),
			(3972, '4', '04', '02', '02', '002', 'JARINGAN DISTRIBUSI TEGANGAN 1 S/D 20 KVA'),
			(3973, '4', '04', '02', '02', '003', 'JARINGAN DISTRIBUSI TEGANGAN DIBAWAH 1 KVA'),
			(3974, '4', '04', '02', '02', '999', 'JARINGAN DISTRIBUSI LAINNYA'),
			(3975, '4', '04', '02', '99', '000', 'JARINGAN LISTRIK LAINNYA'),
			(3976, '4', '04', '02', '99', '999', 'JARINGAN LISTRIK LAINNYA'),
			(3977, '4', '04', '03', '00', '000', 'JARINGAN TELEPON'),
			(3978, '4', '04', '03', '01', '000', 'JARINGAN TELEPON DIATAS TANAH'),
			(3979, '4', '04', '03', '01', '001', 'JARINGAN TELEPON DIATAS TANAH KAPASITAS KECIL'),
			(3980, '4', '04', '03', '01', '002', 'JARINGAN TELEPON DIATAS TANAH KAPASITAS SEDANG'),
			(3981, '4', '04', '03', '01', '003', 'JARINGAN TELEPON DIATAS TANAH KAPASITAS BESAR'),
			(3982, '4', '04', '03', '01', '999', 'JARINGAN TELEPON DIATAS TANAH LAINNYA'),
			(3983, '4', '04', '03', '02', '000', 'JARINGAN TELEPON DIBAWAH TANAH'),
			(3984, '4', '04', '03', '02', '001', 'JARINGAN TELEPON DIBAWAH TANAH KAPASITAS KECIL'),
			(3985, '4', '04', '03', '02', '002', 'JARINGAN TELEPON DIBAWAH TANAH KAPASITAS SEDANG'),
			(3986, '4', '04', '03', '02', '003', 'JARINGAN TELEPON DIBAWAH TANAH KAPASITAS BESAR'),
			(3987, '4', '04', '03', '02', '999', 'JARINGAN TELEPON DIBAWAH TANAH LAINNYA'),
			(3988, '4', '04', '03', '03', '000', 'JARINGAN TELEPON DIDALAM AIR'),
			(3989, '4', '04', '03', '03', '001', 'JARINGAN TELEPON DIDALAM AIR KAPASITAS KECIL'),
			(3990, '4', '04', '03', '03', '002', 'JARINGAN TELEPON DIDALAM AIR KAPASITAS SEDANG'),
			(3991, '4', '04', '03', '03', '003', 'JARINGAN TELEPON DIDALAM AIR KAPASITAS BESAR'),
			(3992, '4', '04', '03', '03', '999', 'JARINGAN TELEPON DIDALAM AIR LAINNYA'),
			(3993, '4', '04', '03', '04', '000', 'JARINGAN DENGAN MEDIA UDARA'),
			(3994, '4', '04', '03', '04', '001', 'JARINGAN SATELIT'),
			(3995, '4', '04', '03', '04', '002', 'JARINGAN RADIO'),
			(3996, '4', '04', '03', '04', '999', 'JARINGAN DENGAN MEDIA UDARA LAINNYA'),
			(3997, '4', '04', '03', '99', '000', 'JARINGAN TELEPON LAINNYA'),
			(3998, '4', '04', '03', '99', '999', 'JARINGAN TELEPON LAINNYA'),
			(3999, '4', '04', '04', '00', '000', 'JARINGAN GAS'),
			(4000, '4', '04', '04', '01', '000', 'JARINGAN PIPA GAS TRANSMISI'),
			(4001, '4', '04', '04', '01', '001', 'JARINGAN PIPA BAJA'),
			(4002, '4', '04', '04', '01', '999', 'JARINGAN PIPA GAS TRANSMISI LAINNYA'),
			(4003, '4', '04', '04', '02', '000', 'JARINGAN PIPA DISTRIBUSI'),
			(4004, '4', '04', '04', '02', '001', 'JARINGAN PIPA DISTRIBUSI TEKANAN TINGGI'),
			(4005, '4', '04', '04', '02', '002', 'JARINGAN PIPA DISTRIBUSI TEKANAN MENENGAH PIPA BAJA'),
			(4006, '4', '04', '04', '02', '003', 'JARINGAN PIPA DISTRIBUSI TEKANAN MENENGAH PIPA PE'),
			(4007, '4', '04', '04', '02', '004', 'JARINGAN PIPA DISTRIBUSI TEKANAN RENDAH PIPA BAJA'),
			(4008, '4', '04', '04', '02', '005', 'JARINGAN PIPA DISTRIBUSI TEKANAN RENDAH PIPA PC'),
			(4009, '4', '04', '04', '02', '999', 'JARINGAN PIPA DISTRIBUSI LAINNYA'),
			(4010, '4', '04', '04', '03', '000', 'JARINGAN PIPA DINAS'),
			(4011, '4', '04', '04', '03', '001', 'JARINGAN PIPA DINAS PIPA BAJA'),
			(4012, '4', '04', '04', '03', '002', 'JARINGAN PIPA DINAS PIPA PE'),
			(4013, '4', '04', '04', '03', '999', 'JARINGAN PIPA DINAS LAINNYA'),
			(4014, '4', '04', '04', '04', '000', 'JARINGAN BBM'),
			(4015, '4', '04', '04', '04', '001', 'JARINGAN BBM BENSIN'),
			(4016, '4', '04', '04', '04', '002', 'JARINGAN BBM SOLAR'),
			(4017, '4', '04', '04', '04', '003', 'JARINGAN BBM MINYAK TANAH'),
			(4018, '4', '04', '04', '04', '999', 'JARINGAN BBM LAINNYA'),
			(4019, '4', '04', '04', '99', '000', 'JARINGAN GAS LAINNYA'),
			(4020, '4', '04', '04', '99', '999', 'JARINGAN GAS LAINNYA'),
			(4021, '5', '00', '00', '00', '000', 'ASET TETAP LAINNYA'),
			(4022, '5', '01', '00', '00', '000', 'BAHAN PERPUSTAKAAN'),
			(4023, '5', '01', '01', '00', '000', 'BAHAN PERPUSTAKAAN TERCETAK'),
			(4024, '5', '01', '01', '01', '000', 'BUKU'),
			(4025, '5', '01', '01', '01', '001', 'MONOGRAF'),
			(4026, '5', '01', '01', '01', '002', 'REFERENSI'),
			(4027, '5', '01', '01', '01', '999', 'BUKU LAINNYA'),
			(4028, '5', '01', '01', '02', '000', 'SERIAL'),
			(4029, '5', '01', '01', '02', '001', 'SURAT KABAR'),
			(4030, '5', '01', '01', '02', '002', 'MAJALAH'),
			(4031, '5', '01', '01', '02', '003', 'BULETIN'),
			(4032, '5', '01', '01', '02', '004', 'LAPORAN'),
			(4033, '5', '01', '01', '02', '999', 'SERIAL LAINNYA'),
			(4034, '5', '01', '01', '99', '000', 'TERCETAK LAINNYA'),
			(4035, '5', '01', '01', '99', '999', 'BAHAN PERPUSTAKAAN TERCETAK LAINNYA'),
			(4036, '5', '01', '02', '00', '000', 'BAHAN PERPUSTAKAAN TEREKAM DAN BENTUK MIKRO'),
			(4037, '5', '01', '02', '01', '000', 'AUDIO VISUAL'),
			(4038, '5', '01', '02', '01', '001', 'KASET'),
			(4039, '5', '01', '02', '01', '002', 'VIDEO'),
			(4040, '5', '01', '02', '01', '003', 'CD/VCD/DVD/LD'),
			(4041, '5', '01', '02', '01', '004', 'PITA FILM'),
			(4042, '5', '01', '02', '01', '005', 'PITA SUARA'),
			(4043, '5', '01', '02', '01', '006', 'PIRINGAN HITAM'),
			(4044, '5', '01', '02', '01', '028', 'PETA DIGITAL'),
			(4045, '5', '01', '02', '01', '999', 'AUDIO VISUAL LAINNYA'),
			(4046, '5', '01', '02', '02', '000', 'BENTUK MIKRO (MICROFORM)'),
			(4047, '5', '01', '02', '02', '001', 'MIKROFILM'),
			(4048, '5', '01', '02', '02', '002', 'MIKROFISCH'),
			(4049, '5', '01', '02', '02', '003', 'SLIDE'),
			(4050, '5', '01', '02', '02', '999', 'BENTUK MIKRO/MIKROFORM LAINNYA'),
			(4051, '5', '01', '02', '99', '000', 'TEREKAM DAN BENTUK MIKRO LAINNYA'),
			(4052, '5', '01', '02', '99', '999', 'BAHAN PERPUSTAKAAN TEREKAM DAN BENTUK MIKRO LAINNYA'),
			(4053, '5', '01', '03', '00', '000', '\"KARTOGRAFI'),
			(4054, '5', '01', '03', '01', '000', 'BAHAN KARTOGRAFI'),
			(4055, '5', '01', '03', '01', '001', 'PETA (MAP)'),
			(4056, '5', '01', '03', '01', '002', 'ATLAS'),
			(4057, '5', '01', '03', '01', '003', 'BLUE PRINT'),
			(4058, '5', '01', '03', '01', '004', 'BOLA DUNIA (GLOBE)'),
			(4059, '5', '01', '03', '01', '999', 'BAHAN KARTOGRAFI LAINNYA'),
			(4060, '5', '01', '03', '02', '000', 'NASKAH (MANUSKRIP) / ASLI'),
			(4061, '5', '01', '03', '02', '001', 'NASKAH/MANUSKRIP BERBAHAN KERTAS'),
			(4062, '5', '01', '03', '02', '002', 'NASKAH/MANUSKRIP BERBAHAN DAUN'),
			(4063, '5', '01', '03', '02', '003', 'NASKAH/MANUSKRIP BERBAHAN KAYU'),
			(4064, '5', '01', '03', '02', '004', 'NASKAH/MANUSKRIP BERBAHAN BAMBU'),
			(4065, '5', '01', '03', '02', '005', 'NASKAH/MANUSKRIP BERBAHAN KULIT KAYU'),
			(4066, '5', '01', '03', '02', '006', 'NASKAH/MANUSKRIP BERBAHAN KULIT BINATANG'),
			(4067, '5', '01', '03', '02', '007', 'NASKAH/MANUSKRIP BERBAHAN TULANG/TANDUK'),
			(4068, '5', '01', '03', '02', '008', 'NASKAH/MANUSKRIP BERBAHAN TEMPURUNG'),
			(4069, '5', '01', '03', '02', '999', 'NASKAH/MANUSKRIP BERBAHAN LAINNYA'),
			(4070, '5', '01', '03', '03', '000', 'LUKISAN DAN UKIRAN'),
			(4071, '5', '01', '03', '03', '001', 'LUKISAN KANVAS'),
			(4072, '5', '01', '03', '03', '002', '\"LUKISAN BATU'),
			(4073, '5', '01', '03', '03', '003', 'UKIRAN KAYU DAN SEJENISNYA'),
			(4074, '5', '01', '03', '03', '004', 'UKIRAN LOGAM DAN SEJENISNYA'),
			(4075, '5', '01', '03', '03', '005', 'UKIRAN BATU DAN SEJENISNYA'),
			(4076, '5', '01', '03', '03', '999', 'UKIRAN DAN LUKISAN LAINNYA'),
			(4077, '5', '01', '03', '99', '000', '\"KARTOGRAFI'),
			(4078, '5', '01', '03', '99', '999', '\"KARTOGRAFI'),
			(4079, '5', '02', '00', '00', '000', 'BARANG BERCORAK KESENIAN/KEBUDAYAAN/OLAHRAGA'),
			(4080, '5', '02', '01', '00', '000', 'BARANG BERCORAK KESENIAN'),
			(4081, '5', '02', '01', '01', '000', 'ALAT MUSIK'),
			(4082, '5', '02', '01', '01', '001', 'ALAT MUSIK TRADISIONAL/DAERAH'),
			(4083, '5', '02', '01', '01', '002', 'ALAT MUSIK MODERN/BAND'),
			(4084, '5', '02', '01', '01', '999', 'ALAT MUSIK LAINNYA'),
			(4085, '5', '02', '01', '02', '000', 'LUKISAN'),
			(4086, '5', '02', '01', '02', '001', 'LUKISAN CAT AIR'),
			(4087, '5', '02', '01', '02', '002', 'SULAMAN / TEMPELAN'),
			(4088, '5', '02', '01', '02', '003', 'LUKISAN CAT MINYAK'),
			(4089, '5', '02', '01', '02', '004', 'LUKISAN BULU'),
			(4090, '5', '02', '01', '02', '005', 'SENI RELIEF'),
			(4091, '5', '02', '01', '02', '999', 'LUKISAN LAINNYA'),
			(4092, '5', '02', '01', '03', '000', 'ALAT PERAGA KESENIAN'),
			(4093, '5', '02', '01', '03', '001', 'WAYANG GOLEK'),
			(4094, '5', '02', '01', '03', '002', 'WAYANG KULIT'),
			(4095, '5', '02', '01', '03', '999', 'ALAT PERAGA KESENIAN LAINNYA'),
			(4096, '5', '02', '01', '99', '000', 'BARANG BERCORAK KESENIAN LAINNYA'),
			(4097, '5', '02', '01', '99', '999', 'BARANG BERCORAK KESENIAN LAINNYA'),
			(4098, '5', '02', '02', '00', '000', 'ALAT BERCORAK KEBUDAYAAN'),
			(4099, '5', '02', '02', '01', '000', 'PAHATAN'),
			(4100, '5', '02', '02', '01', '001', 'PAHATAN BATU'),
			(4101, '5', '02', '02', '01', '002', 'PAHATAN KAYU'),
			(4102, '5', '02', '02', '01', '003', 'PAHATAN LOGAM'),
			(4103, '5', '02', '02', '01', '999', 'PAHATAN LAINNYA'),
			(4104, '5', '02', '02', '02', '000', '\"MAKET'),
			(4105, '5', '02', '02', '02', '001', 'MAKET/MINIATUR/REPLIKA'),
			(4106, '5', '02', '02', '02', '002', 'FOTO DOKUMEN'),
			(4107, '5', '02', '02', '02', '003', 'NASKAH KUNO'),
			(4108, '5', '02', '02', '02', '004', 'MATA UANG/ NUMISMATIK'),
			(4109, '5', '02', '02', '02', '005', 'PERHIASAN'),
			(4110, '5', '02', '02', '02', '006', 'BARANG KERAMIK/ GERABAH'),
			(4111, '5', '02', '02', '02', '007', 'ARCA/ PATUNG'),
			(4112, '5', '02', '02', '02', '008', 'BENDA KUNO/ UNIK'),
			(4113, '5', '02', '02', '02', '009', 'FOSIL'),
			(4114, '5', '02', '02', '02', '010', 'MUMY'),
			(4115, '5', '02', '02', '02', '999', 'MAKET DAN FOTO DOKUMEN LAINNYA'),
			(4116, '5', '02', '02', '99', '000', 'ALAT BERCORAK KEBUDAYAAN LAINNYA'),
			(4117, '5', '02', '02', '99', '999', 'ALAT BERCORAK KEBUDAYAAN LAINNYA'),
			(4118, '5', '02', '03', '00', '000', 'TANDA PENGHARGAAN BIDANG OLAH RAGA'),
			(4119, '5', '02', '03', '01', '000', 'TANDA PENGHARGAAN'),
			(4120, '5', '02', '03', '01', '001', 'PIALA'),
			(4121, '5', '02', '03', '01', '002', 'MEDALI'),
			(4122, '5', '02', '03', '01', '003', 'PIAGAM'),
			(4123, '5', '02', '03', '01', '999', 'TANDA PENGHARGAAN LAINNYA'),
			(4124, '5', '02', '03', '99', '000', 'TANDA PENGHARGAAN BIDANG OLAH RAGA LAINNYA'),
			(4125, '5', '02', '03', '99', '999', 'TANDA PENGHARGAAN BIDANG OLAH RAGA LAINNYA'),
			(4126, '5', '03', '00', '00', '000', 'HEWAN'),
			(4127, '5', '03', '01', '00', '000', 'HEWAN PIARAAN'),
			(4128, '5', '03', '01', '01', '000', 'HEWAN PENGAMAN'),
			(4129, '5', '03', '01', '01', '001', 'ANJING PELACAK'),
			(4130, '5', '03', '01', '01', '002', 'ANJING PENJAGA'),
			(4131, '5', '03', '01', '01', '999', 'HEWAN PENGAMAN LAINNYA'),
			(4132, '5', '03', '01', '02', '000', 'HEWAN PENGANGKUT'),
			(4133, '5', '03', '01', '02', '001', 'GAJAH'),
			(4134, '5', '03', '01', '02', '002', 'KUDA (HEWAN PENGANGKUT)'),
			(4135, '5', '03', '01', '02', '999', 'HEWAN PENGANGKUT LAINNYA'),
			(4136, '5', '03', '01', '99', '000', 'HEWAN PIARAAN LAINNYA'),
			(4137, '5', '03', '01', '99', '999', 'HEWAN PIARAAN LAINNYA'),
			(4138, '5', '03', '02', '00', '000', 'TERNAK'),
			(4139, '5', '03', '02', '01', '000', 'TERNAK POTONG'),
			(4140, '5', '03', '02', '01', '001', 'BABI'),
			(4141, '5', '03', '02', '01', '002', 'DOMBA'),
			(4142, '5', '03', '02', '01', '003', 'KAMBING'),
			(4143, '5', '03', '02', '01', '004', 'KERBAU'),
			(4144, '5', '03', '02', '01', '005', 'SAPI POTONG'),
			(4145, '5', '03', '02', '01', '999', 'TERNAK POTONG LAINNYA'),
			(4146, '5', '03', '02', '02', '000', 'TERNAK PERAH'),
			(4147, '5', '03', '02', '02', '001', 'SAPI PERAH'),
			(4148, '5', '03', '02', '02', '002', 'DOMBA PERAH'),
			(4149, '5', '03', '02', '02', '003', 'KAMBING PERAH'),
			(4150, '5', '03', '02', '02', '999', 'TERNAK PERAH LAINNYA'),
			(4151, '5', '03', '02', '03', '000', 'TERNAK UNGGAS'),
			(4152, '5', '03', '02', '03', '001', 'AYAM'),
			(4153, '5', '03', '02', '03', '002', 'BURUNG'),
			(4154, '5', '03', '02', '03', '003', 'ITIK'),
			(4155, '5', '03', '02', '03', '999', 'TERNAK UNGGAS LAINNYA'),
			(4156, '5', '03', '02', '99', '000', 'TERNAK LAINNYA'),
			(4157, '5', '03', '02', '99', '999', 'TERNAK LAINNYA'),
			(4158, '5', '03', '03', '00', '000', 'HEWAN LAINNYA'),
			(4159, '5', '03', '03', '01', '000', 'HEWAN LAINNYA'),
			(4160, '5', '03', '03', '01', '001', 'HEWAN LAINNYA'),
			(4161, '5', '04', '00', '00', '000', 'IKAN'),
			(4162, '5', '04', '01', '00', '000', 'IKAN BERSIRIP (PISCES/IKAN BERSIRIP)'),
			(4163, '5', '04', '01', '01', '000', 'IKAN BUDIDAYA'),
			(4164, '5', '04', '01', '01', '001', 'IKAN AIR TAWAR BUDIDAYA'),
			(4165, '5', '04', '01', '01', '002', 'IKAN AIR LAUT BUDIDAYA'),
			(4166, '5', '04', '01', '01', '003', 'IKAN AIR PAYAU BUDIDAYA'),
			(4167, '5', '04', '01', '01', '004', 'IKAN HIAS AIR TAWAR BUDIDAYA'),
			(4168, '5', '04', '01', '01', '005', 'IKAN HIAS AIR PAYAU/LAUT BUDIDAYA'),
			(4169, '5', '04', '01', '01', '999', 'IKAN BUDIDAYA LAINNYA'),
			(4170, '5', '04', '02', '00', '000', '\"CRUSTEA (UDANG'),
			(4171, '5', '04', '02', '01', '000', '\"CRUSTEA BUDIDAYA (UDANG'),
			(4172, '5', '04', '02', '01', '001', 'UDANG'),
			(4173, '5', '04', '02', '01', '002', 'RAJUNGAN'),
			(4174, '5', '04', '02', '01', '003', 'KEPITING'),
			(4175, '5', '04', '02', '01', '999', '\"CRUSTEA (UDANG'),
			(4176, '5', '04', '03', '00', '000', '\"MOLLUSCA (KERANG'),
			(4177, '5', '04', '03', '01', '000', '\"MOLLUSCA BUDIDAYA (KERANG'),
			(4178, '5', '04', '03', '01', '001', 'KERANG'),
			(4179, '5', '04', '03', '01', '002', 'TIRAM'),
			(4180, '5', '04', '03', '01', '003', 'CUMI-CUMI'),
			(4181, '5', '04', '03', '01', '004', 'GURITA'),
			(4182, '5', '04', '03', '01', '005', 'SIPUT'),
			(4183, '5', '04', '03', '01', '999', '\"MOLLUSCA (KERANG'),
			(4184, '5', '04', '04', '00', '000', 'COELENTERATA (UBUR-UBUR DAN SEBANGSANYA)'),
			(4185, '5', '04', '04', '01', '000', 'COELENTERATA BUDIDAYA (UBUR-UBUR DAN SEBANGSANYA)'),
			(4186, '5', '04', '04', '01', '001', 'UBUR-UBUR BUDIDAYA'),
			(4187, '5', '04', '04', '01', '999', 'COELENTERATA (UBUR-UBUR DAN SEBANGSANYA) LAINNYA'),
			(4188, '5', '04', '05', '00', '000', '\"ECHINODERMATA (TRIPANG'),
			(4189, '5', '04', '05', '01', '000', '\"ECHINODERMATA BUDIDAYA (TRIPANG'),
			(4190, '5', '04', '05', '01', '001', 'TERIPANG'),
			(4191, '5', '04', '05', '01', '002', 'BULU BABI'),
			(4192, '5', '04', '05', '01', '999', '\"ECHINODERMATA (TRIPANG'),
			(4193, '5', '04', '06', '00', '000', 'AMPHIBIA (KODOK DAN SEBANGSANYA)'),
			(4194, '5', '04', '06', '01', '000', 'AMPHIBIA BUDIDAYA (KODOK DAN SEBANGSANYA)'),
			(4195, '5', '04', '06', '01', '001', 'KODOK'),
			(4196, '5', '04', '06', '01', '002', 'SEBANGSA KODOK'),
			(4197, '5', '04', '06', '01', '999', 'AMPHIBIA (KODOK DAN SEBANGSANYA) LAINNYA'),
			(4198, '5', '04', '07', '00', '000', '\"REPTILIA (BUAYA'),
			(4199, '5', '04', '07', '01', '000', '\"REPTILIA BUDIDAYA (BUAYA'),
			(4200, '5', '04', '07', '01', '001', 'PENYU'),
			(4201, '5', '04', '07', '01', '002', 'KURA-KURA'),
			(4202, '5', '04', '07', '01', '003', 'BIAWAK'),
			(4203, '5', '04', '07', '01', '004', 'ULAR AIR'),
			(4204, '5', '04', '07', '01', '999', '\"REPTILIA (BUAYA'),
			(4205, '5', '04', '08', '00', '000', '\"MAMMALIA (PAUS'),
			(4206, '5', '04', '08', '01', '000', '\"MAMMALIA BUDIDAYA (PAUS'),
			(4207, '5', '04', '08', '01', '001', 'PAUS'),
			(4208, '5', '04', '08', '01', '002', 'LUMBA-LUMBA'),
			(4209, '5', '04', '08', '01', '003', 'PESUT'),
			(4210, '5', '04', '08', '01', '004', 'DUYUNG'),
			(4211, '5', '04', '08', '01', '999', '\"MAMMALIA (PAUS'),
			(4212, '5', '04', '09', '00', '000', 'ALGAE (RUMPUT LAUT DAN TUMBUH-TUMBUHAN LAIN YANG HIDUP DI DALAM AIR)'),
			(4213, '5', '04', '09', '01', '000', 'ALGAE BUDIDAYA (RUMPUT LAUT DAN TUMBUH-TUMBUHAN LAIN YANG HIDUP DI DALAM AIR)'),
			(4214, '5', '04', '09', '01', '001', 'RUMPUT LAUT'),
			(4215, '5', '04', '09', '01', '002', 'TUMBUH-TUMBUHAN LAIN YANG HIDUP DI DALAM AIR'),
			(4216, '5', '04', '09', '01', '999', 'ALGAE (RUMPUT LAUT DAN TUMBUH-TUMBUHAN LAIN YANG HIDUP DI DALAM AIR) LAINNYA'),
			(4217, '5', '04', '10', '00', '000', 'BIOTA PERAIRAN LAINNYA'),
			(4218, '5', '04', '10', '01', '000', 'BUDIDAYA BIOTA PERAIRAN LAINNYA'),
			(4219, '5', '04', '10', '01', '001', 'BIOTA PERAIRAN LAINNYA'),
			(4220, '5', '05', '00', '00', '000', 'TANAMAN'),
			(4221, '5', '05', '01', '00', '000', 'TANAMAN'),
			(4222, '5', '05', '01', '01', '000', 'TANAMAN'),
			(4223, '5', '05', '01', '01', '001', 'TANAMAN KERAS'),
			(4224, '5', '05', '01', '01', '002', 'TANAMAN INDUSTRI'),
			(4225, '5', '05', '01', '01', '003', 'TANAMAN PERKEBUNAN'),
			(4226, '5', '05', '01', '01', '004', 'TANAMAN HORTIKULTURA'),
			(4227, '5', '05', '01', '01', '005', 'TANAMAN PANGAN'),
			(4228, '5', '05', '01', '01', '006', 'TANAMAN HIAS'),
			(4229, '5', '05', '01', '01', '007', 'TANAMAN OBAT'),
			(4230, '5', '05', '01', '01', '008', 'TANAMAN PLASMA'),
			(4231, '5', '05', '01', '01', '999', 'TANAMAN LAINNYA'),
			(4232, '5', '06', '00', '00', '000', 'ASET TETAP DALAM RENOVASI'),
			(4233, '5', '06', '01', '00', '000', 'ASET TETAP DALAM RENOVASI'),
			(4234, '5', '06', '01', '01', '000', 'ASET TETAP DALAM RENOVASI'),
			(4235, '5', '06', '01', '01', '001', 'TANAH DALAM RENOVASI'),
			(4236, '5', '06', '01', '01', '002', 'PERALATAN DAN MESIN DALAM RENOVASI'),
			(4237, '5', '06', '01', '01', '003', 'GEDUNG DAN BANGUNAN DALAM RENOVASI'),
			(4238, '5', '06', '01', '01', '004', '\"JALAN'),
			(4239, '5', '06', '01', '01', '005', 'ASET TETAP LAINNYA DALAM RENOVASI'),
			(4240, '5', '06', '01', '01', '999', 'ASET TETAP DALAM RENOVASI LAINNYA'),
			(4241, '6', '00', '00', '00', '000', 'KONSTRUKSI DALAM PENGERJAAN'),
			(4242, '6', '01', '00', '00', '000', 'KONSTRUKSI DALAM PENGERJAAN'),
			(4243, '6', '01', '01', '00', '000', 'KONSTRUKSI DALAM PENGERJAAN'),
			(4244, '6', '01', '01', '01', '000', 'KONSTRUKSI DALAM PENGERJAAN'),
			(4245, '6', '01', '01', '01', '001', 'TANAH DALAM PENGERJAAN'),
			(4246, '6', '01', '01', '01', '002', 'PERALATAN DAN MESIN DALAM PENGERJAAN'),
			(4247, '6', '01', '01', '01', '003', 'GEDUNG DAN BANGUNAN DALAM PENGERJAAN'),
			(4248, '6', '01', '01', '01', '004', '\"JALAN'),
			(4249, '6', '01', '01', '01', '005', 'ASET TETAP LAINNYA DALAM PENGERJAAN'),
			(4250, '6', '01', '01', '01', '999', 'KONSTRUKSI DALAM PENGERJAAN LAINNYA')
		";

		$this->db->query($query);
	}

		$fields['berat_lahir'] = array(
				'type' => 'SMALLINT',
				'constraint' => 6,
			  'null' => TRUE,
				'default' => NULL
		);
	  $this->dbforge->modify_column('tweb_penduduk', $fields);
		// Tambahkan setting aplikasi untuk format penomoran surat
		$query = $this->db->select('1')->where('key', 'format_nomor_surat')->get('setting_aplikasi');
		if (!$query->result())
		{
			$data = array(
				'key' => 'format_nomor_surat',
				'value' => '[kode_surat]/[nomor_surat, 3]/PEM/[tahun]',
				'keterangan' => 'Fomat penomoran surat'
			);
			$this->db->insert('setting_aplikasi', $data);
		}
		// Ubah setting aplikasi current_version menjadi readonly
		$this->db->where('key', 'current_version')->update('setting_aplikasi', array('kategori' => 'readonly'));
		// Tambahkan setting aplikasi untuk jabatan pimpinan desa
		$query = $this->db->select('1')->where('key', 'sebutan_pimpinan_desa')->get('setting_aplikasi');
		if (!$query->result())
		{
			$data = array(
				'key' => 'sebutan_pimpinan_desa',
				'value' => 'Kepala Desa',
				'keterangan' => 'Sebutan pimpinan desa',
				'kategori' => 'pemerintahan'
			);
			$this->db->insert('setting_aplikasi', $data);
		}
		// Tambah folder desa untuk menyimpan kop surat
		if (!file_exists('/desa/surat/raw'))
		{
			mkdir('desa/surat/raw');
		}
		// Tambah Surat Pengantar Permohonan Penerbitan Buku Pas Lintas
		$data = array();
		$data[] = array(
			'nama'=>'Pengantar Permohonan Penerbitan Buku Pas Lintas',
			'url_surat'=>'surat_permohonan_penerbitan_buku_pas_lintas',
			'kode_surat'=>'S-43',
			'jenis'=>1);
		// Tambah surat keterangan penghasilan ayah
		$data[] = array(
			'nama'=>'Keterangan Penghasilan Ayah',
			'url_surat'=>'surat_ket_penghasilan_ayah',
			'kode_surat'=>'S-44',
			'jenis'=>1);
		// Tambah surat keterangan penghasilan ibu
		$data[] = array(
			'nama'=>'Keterangan Penghasilan Ibu',
			'url_surat'=>'surat_ket_penghasilan_ibu',
			'kode_surat'=>'S-45',
			'jenis'=>1);
		foreach ($data as $surat)
		{
			$sql = $this->db->insert_string('tweb_surat_format', $surat);
			$sql .= " ON DUPLICATE KEY UPDATE
					nama = VALUES(nama),
					url_surat = VALUES(url_surat),
					kode_surat = VALUES(kode_surat),
					jenis = VALUES(jenis)";
			$this->db->query($sql);
		}
  }

  private function migrasi_1905_ke_1906()
  {
  	// Tambah kolom waktu update dan user pengupdate
  	if (!$this->db->field_exists('created_at', 'tweb_penduduk'))
  	{
			// Tambah kolom
			$this->dbforge->add_field("created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$fields = array();
			$fields['created_by'] = array(
					'type' => 'int',
					'constraint' => 11,
				  'null' => FALSE,
			);
			$this->dbforge->add_column('tweb_penduduk', $fields);
		}
  	if (!$this->db->field_exists('updated_at', 'tweb_penduduk'))
  	{
			// Tambah kolom
			$this->dbforge->add_field("updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");
			$fields = array();
			$fields['updated_by'] = array(
					'type' => 'int',
					'constraint' => 11,
				  'null' => FALSE,
			);
			$this->dbforge->add_column('tweb_penduduk', $fields);
		}
		else
		{
			$fields = array();
			$fields['updated_by'] = array(
					'type' => 'INT',
					'constraint' => 11,
				  'null' => TRUE,
					'default' => NULL
			);
		  $this->dbforge->modify_column('tweb_penduduk', $fields);
		}

  	// Tambah menu teks berjalan
		$data = array(
			'id' => '64',
			'modul' => 'Teks Berjalan',
			'url' => 'teks_berjalan',
			'aktif' => '1',
			'ikon' => 'fa-ellipsis-h',
			'urut' => '9',
			'level' => '2',
			'parent' => '13',
			'hidden' => '0',
			'ikon_kecil' => 'fa-ellipsis-h'
		);
		$sql = $this->db->insert_string('setting_modul', $data) . " ON DUPLICATE KEY UPDATE url = VALUES(url), ikon = VALUES(ikon), ikon_kecil = VALUES(ikon_kecil)";
		$this->db->query($sql);

		if (!$this->db->table_exists('teks_berjalan'))
		{
			$query = "
			CREATE TABLE `teks_berjalan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`teks` text,
				`urut` int(5),
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11),
				`status` int(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);

			$setting_teks_berjalan = $this->db->select('id, value')->where('key','isi_teks_berjalan')->get('setting_aplikasi')->row();
			if ($setting_teks_berjalan)
			{
				// ambil teks, tulis ke tabel teks_berjalan
				// hapus setting
				$isi_teks = $setting_teks_berjalan->value;
				$data = array(
					'teks' => $isi_teks,
					'created_by' => $this->session->user
				);
				$this->db->insert('teks_berjalan', $data);
				$this->db->where('key','isi_teks_berjalan')->delete('setting_aplikasi');
			}
			else
			{
				// ambil teks dari artikel, tulis ke tabel teks_berjalan
				// hapus artikel
				$id_kategori = $this->db->select('id')->where('kategori', 'teks_berjalan')->limit(1)->get('kategori')->row()->id;
				if ($id_kategori)
				{
					// Ambil teks dari artikel
					$teks = $this->db->select('a.isi, a.enabled')
						->from('artikel a')
						->join('kategori k', 'a.id_kategori = k.id', 'left')
						->where('k.kategori', 'teks_berjalan')
						->get()->result_array();
					foreach ($teks as $data)
					{
						$isi_teks = strip_tags($data['isi']);
						$isi = array(
							'teks' => $isi_teks,
							'status' => $data['enabled'],
							'created_by' => $this->session->user
						);
						$this->db->insert('teks_berjalan', $isi);
					}
					// Hapus artikel dan kategori teks berjalan
					$this->db->where('id_kategori', $id_kategori)->delete('artikel');
					$this->db->where('kategori', 'teks_berjalan')->delete('kategori');
				}
			}
		}
  	// Tambah tautan pada teks berjalan
  	if (!$this->db->field_exists('tautan', 'teks_berjalan'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['tautan'] = array(
					'type' => 'varchar',
					'constraint' => 150,
			);
			$fields['judul_tautan'] = array(
					'type' => 'varchar',
					'constraint' => 150,
			);
			$this->dbforge->add_column('teks_berjalan', $fields);
		}

  	// Hapus menu SID dan Donasi
		$this->db->where('id', 16)->delete('setting_modul');
		$this->db->where('id', 19)->delete('setting_modul');

  	$fields = $this->db->field_data('tweb_penduduk');
  	$lookup = array_column($fields, NULL, 'name');   // re-index by 'name'
  	$field_berat_lahir = $lookup['berat_lahir'];
  	if (strtolower($field_berat_lahir->type) == 'varchar')
  	{
	  	// Ubah berat lahir dari kg menjadi gram
	  	$list_penduduk = $this->db->select('id, berat_lahir')->get('tweb_penduduk')->result_array();
	  	foreach ($list_penduduk as $penduduk)
	  	{
	  		// Kolom berat_lahir tersimpan sebagai varchar
	  		$berat_lahir = (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/','', $penduduk['berat_lahir']));
	  		if ($berat_lahir < 100.0)
	  		{
	  			$berat_lahir = (int)($berat_lahir * 1000.0);
	  			$this->db->where('id', $penduduk['id'])->update('tweb_penduduk', array('berat_lahir' => $berat_lahir));
	  		}
	  	}
	  	// Ganti kolom berat_lahir menjadi bilangan
		  $this->dbforge->modify_column('tweb_penduduk', array('berat_lahir' => array('type' => 'SMALLINT')));
  	}
  	// Di tweb_penduduk ubah kelahiran_anak_ke supaya default NULL
	  $this->dbforge->modify_column('tweb_penduduk', array('kelahiran_anak_ke' => array('type' => 'TINYINT', 'constraint' => 2, 'default' => NULL)));

	  // Ubah kolom tweb_penduduk supaya boleh null
		$fields = array();
		$fields['ktp_el'] = array(
				'type' => 'TINYINT',
				'constraint' => 4,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['status_rekam'] = array(
				'type' => 'TINYINT',
				'constraint' => 4,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['tempat_dilahirkan'] = array(
				'type' => 'TINYINT',
				'constraint' => 2,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['jenis_kelahiran'] = array(
				'type' => 'TINYINT',
				'constraint' => 2,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['penolong_kelahiran'] = array(
				'type' => 'TINYINT',
				'constraint' => 2,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['panjang_lahir'] = array(
				'type' => 'VARCHAR',
				'constraint' => 10,
			  'null' => TRUE,
				'default' => NULL
		);
		$fields['sakit_menahun_id'] = array(
				'type' => 'INT',
				'constraint' => 11,
			  'null' => TRUE,
				'default' => NULL
		);
	  $this->dbforge->modify_column('tweb_penduduk', $fields);
  }

  private function migrasi_1904_ke_1905()
  {
  	// Tambah kolom penduduk
  	if (!$this->db->field_exists('tag_id_card', 'tweb_penduduk'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['tag_id_card'] = array(
					'type' => 'VARCHAR',
					'constraint' => 15,
					'default' => NULL
			);
			$this->dbforge->add_column('tweb_penduduk', $fields);
		}
  	// Tambah form admin aparatur desa
		$this->db->where('isi','aparatur_desa.php')->update('widget',array('form_admin'=>'web_widget/admin/aparatur_desa'));
  	// Konversi data suplemen terdata ke id
  	$jml = $this->db->select('count(id) as jml')
  		->where('id_terdata <>', '0')
  		->where('char_length(id_terdata) <> 16')
  		->get('suplemen_terdata')
  		->row()->jml;
  	if ($jml == 0)
  	{
	  	$terdata = $this->db->select('s.id as s_id, s.id_terdata, s.sasaran,
	  		(case when s.sasaran = 1 then p.id else k.id end) as id')
	  		->from('suplemen_terdata s')
	  		->join('tweb_keluarga k', 'k.no_kk = s.id_terdata', 'left')
	  		->join('tweb_penduduk p', 'p.nik = s.id_terdata', 'left')
	  		->get()
	  		->result_array();
	  	foreach ($terdata as $data)
	  	{
				$this->db
					->where('id', $data['s_id'])
					->update('suplemen_terdata', array('id_terdata' => $data['id']));
	   	}
	  }

		$this->db->where('id', 62)->update('setting_modul', array('url'=>'gis/clear', 'aktif'=>'1'));
		// Tambah surat keterangan penghasilan orangtua
		$data = array(
			'nama'=>'Keterangan Penghasilan Orangtua',
			'url_surat'=>'surat_ket_penghasilan_orangtua',
			'kode_surat'=>'S-42',
			'jenis'=>1);
		$sql = $this->db->insert_string('tweb_surat_format', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis)";
		$this->db->query($sql);
  }

  private function migrasi_1903_ke_1904()
  {
		$this->db->where('id', 59)->update('setting_modul', array('url'=>'dokumen_sekretariat/clear/2', 'aktif'=>'1'));
		$this->db->where('id', 60)->update('setting_modul', array('url'=>'dokumen_sekretariat/clear/3', 'aktif'=>'1'));
  	// Tambah tabel agenda
		$tb = 'agenda';
		if (!$this->db->table_exists($tb))
		{
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'auto_increment' => TRUE
				),
				'id_artikel' => array(
					'type' => 'INT',
					'constraint' => 11
				),
				'tgl_agenda' => array(
					'type' => 'timestamp'
				),
				'koordinator_kegiatan' => array(
					'type' => 'VARCHAR',
					'constraint' => 50
				),
				'lokasi_kegiatan' => array(
					'type' => 'VARCHAR',
					'constraint' => 100
				)
			));
			$this->dbforge->add_key('id', true);
			$this->dbforge->create_table($tb, false, array('ENGINE' => $this->engine));
			$this->dbforge->add_column(
				'agenda',
				array('CONSTRAINT `id_artikel_fk` FOREIGN KEY (`id_artikel`) REFERENCES `artikel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
			);
		}
		// Pindahkan tgl_agenda kalau sudah sempat membuatnya
  	if ($this->db->field_exists('tgl_agenda', 'artikel'))
  	{
  		$data = $this->db->select('id, tgl_agenda')->where('id_kategori', AGENDA)
  			->get('artikel')
  			->result_array();
  		if (count($data))
  		{
	  		$artikel_agenda = array();
	  		foreach ($data as $agenda)
	  		{
	  			$artikel_agenda[] = array('id_artikel'=>$agenda['id'], 'tgl_agenda'=>$agenda['tgl_agenda']);
	  		}
	  		$this->db->insert_batch('agenda', $artikel_agenda);
  		}
			$this->dbforge->drop_column('artikel', 'tgl_agenda');
  	}
		// Tambah tombol media sosial whatsapp
		$query = "
			INSERT INTO media_sosial (id, gambar, link, nama, enabled) VALUES ('6', 'wa.png', '', 'WhatsApp', '1')
			ON DUPLICATE KEY UPDATE
				gambar = VALUES(gambar),
				nama = VALUES(nama)";
		$this->db->query($query);
		// Tambahkan setting aplikasi untuk mengubah warna tema komponen Admin
		$query = $this->db->select('1')->where('key', 'warna_tema_admin')->get('setting_aplikasi');
		if (!$query->result())
		{
			$data = array(
				'key' => 'warna_tema_admin',
				'value' => $setting->value ?: 'skin-purple',
				'jenis' => 'option-value',
				'keterangan' => 'Warna dasar tema komponen Admin'
			);
			$this->db->insert('setting_aplikasi', $data);
			$setting_id = $this->db->insert_id();
			$this->db->insert_batch(
				'setting_aplikasi_options',
				array(
					array('id_setting'=>$setting_id, 'value'=>'skin-blue'),
					array('id_setting'=>$setting_id, 'value'=>'skin-blue-light'),
					array('id_setting'=>$setting_id, 'value'=>'skin-yellow'),
					array('id_setting'=>$setting_id, 'value'=>'skin-yellow-light'),
					array('id_setting'=>$setting_id, 'value'=>'skin-green'),
					array('id_setting'=>$setting_id, 'value'=>'skin-green-light'),
					array('id_setting'=>$setting_id, 'value'=>'skin-purple'),
					array('id_setting'=>$setting_id, 'value'=>'skin-purple-light'),
					array('id_setting'=>$setting_id, 'value'=>'skin-red'),
					array('id_setting'=>$setting_id, 'value'=>'skin-red-light'),
					array('id_setting'=>$setting_id, 'value'=>'skin-black'),
					array('id_setting'=>$setting_id, 'value'=>'skin-black-light')
				)
			);
		}
  }

  private function migrasi_1901_ke_1902()
  {
  	// Ubah judul status hubungan dalam keluarga
  	$this->db->where('id', 9)->update('tweb_penduduk_hubungan', array('nama' => 'FAMILI'));
  	// Perpanjang nomor surat di surat masuk dan keluar
	  $this->dbforge->modify_column('surat_masuk', array('nomor_surat' => array('name'  =>  'nomor_surat', 'type' =>  'VARCHAR',  'constraint'  =>  35 )));
	  $this->dbforge->modify_column('surat_keluar', array('nomor_surat' => array('name'  =>  'nomor_surat', 'type' =>  'VARCHAR',  'constraint'  =>  35 )));
  	// Tambah setting program bantuan yg ditampilkan di dashboard
		$query = $this->db->select('1')->where('key', 'dashboard_program_bantuan')->get('setting_aplikasi');
		$query->result() OR	$this->db->insert('setting_aplikasi', array('key'=>'dashboard_program_bantuan', 'value'=>'1	', 'jenis'=>'int', 'keterangan'=>"ID program bantuan yang ditampilkan di dashboard", 'kategori'=>'dashboard'));
  	// Tambah setting panjang nomor surat
		$query = $this->db->select('1')->where('key', 'panjang_nomor_surat')->get('setting_aplikasi');
		$query->result() OR	$this->db->insert('setting_aplikasi', array('key'=>'panjang_nomor_surat', 'value'=>'', 'jenis'=>'int', 'keterangan'=>"Nomor akan diisi '0' di sebelah kiri, kalau perlu", 'kategori'=>'surat'));
  	// Tambah rincian pindah di log_penduduk
		$tb_option = 'ref_pindah';
		if (!$this->db->table_exists($tb_option))
		{
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'TINYINT',
					'constraint' => 4
				),
				'nama' => array(
					'type' => 'VARCHAR',
					'constraint' => 50
				)
			));
			$this->dbforge->add_key('id', true);
			$this->dbforge->create_table($tb_option, false, array('ENGINE' => $this->engine));
			$this->db->insert_batch(
				$tb_option,
				array(
					array('id'=>1, 'nama'=>'Pindah keluar Desa/Kelurahan'),
					array('id'=>2, 'nama'=>'Pindah keluar Kecamatan'),
					array('id'=>3, 'nama'=>'Pindah keluar Kabupaten/Kota'),
					array('id'=>4, 'nama'=>'Pindah keluar Provinsi'),
				)
			);
		}
  	if (!$this->db->field_exists('ref_pindah', 'log_penduduk'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['ref_pindah'] = array(
					'type' => 'TINYINT',
					'constraint' => 4,
					'default' => 1
			);
			$this->dbforge->add_column('log_penduduk', $fields);
			$this->dbforge->add_column(
				'log_penduduk',
				array('CONSTRAINT `id_ref_pindah` FOREIGN KEY (`ref_pindah`) REFERENCES `ref_pindah` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
			);
  	}
  }

  private function migrasi_1812_ke_1901()
  {
  	// Tambah status dasar 'Tidak Valid'
		$data = array(
			'id' => 9,
			'nama' => 'TIDAK VALID');
		$sql = $this->db->insert_string('tweb_status_dasar', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				nama = VALUES(nama)";
		$this->db->query($sql);
  	// Tambah kolom tweb_desa_pamong
  	if (!$this->db->field_exists('no_hp', 'komentar'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['no_hp'] = array(
					'type' => 'varchar',
					'constraint' => 15,
					'default' => NULL
			);
			$this->dbforge->add_column('komentar', $fields);
  	}

  	// Tambah kolom tweb_desa_pamong
  	if (!$this->db->field_exists('pamong_pangkat', 'tweb_desa_pamong'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['pamong_niap'] = array(
					'type' => 'varchar',
					'constraint' => 20,
					'default' => NULL
			);
			$fields['pamong_pangkat'] = array(
					'type' => 'varchar',
					'constraint' => 20,
					'default' => NULL
			);
			$fields['pamong_nohenti'] = array(
					'type' => 'varchar',
					'constraint' => 20,
					'default' => NULL
			);
			$fields['pamong_tglhenti'] = array(
					'type' => 'date',
					'default' => NULL
			);
			$this->dbforge->add_column('tweb_desa_pamong', $fields);
  	}

  	// Urut tabel tweb_desa_pamong
  	if (!$this->db->field_exists('urut', 'tweb_desa_pamong'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['urut'] = array(
					'type' => 'int',
					'constraint' => 5
			);
			$this->dbforge->add_column('tweb_desa_pamong', $fields);
  	}
		$this->db->where('id', 18)->update('setting_modul', array('url'=>'pengurus/clear', 'aktif'=>'1'));
		$this->db->where('id', 48)->update('setting_modul', array('url'=>'web_widget/clear', 'aktif'=>'1'));
  }

  private function migrasi_1811_ke_1812()
  {
  	// Ubah struktur tabel tweb_desa_pamong
  	if (!$this->db->field_exists('id_pend', 'tweb_desa_pamong'))
  	{
			// Tambah kolom
			$fields = array();
			$fields['id_pend'] = array(
					'type' => 'int',
					'constraint' => 11
			);
			$fields['pamong_tempatlahir'] = array(
					'type' => 'varchar',
					'constraint' => 100,
					'default' => NULL
			);
			$fields['pamong_tanggallahir'] = array(
					'type' => 'date',
					'default' => NULL
			);
			$fields['pamong_sex'] = array(
					'type' => 'tinyint',
					'constraint' => 4,
					'default' => NULL
			);
			$fields['pamong_pendidikan'] = array(
					'type' => 'int',
					'constraint' => 10,
					'default' => NULL
			);
			$fields['pamong_agama'] = array(
					'type' => 'int',
					'constraint' => 10,
					'default' => NULL
			);
			$fields['pamong_nosk'] = array(
					'type' => 'varchar',
					'constraint' => 20,
					'default' => NULL
			);
			$fields['pamong_tglsk'] = array(
					'type' => 'date',
					'default' => NULL
			);
			$fields['pamong_masajab'] = array(
					'type' => 'varchar',
					'constraint' => 120,
					'default' => NULL
			);
			$this->dbforge->add_column('tweb_desa_pamong', $fields);
  	}

  	// Pada tweb_keluarga kosongkan nik_kepala kalau tdk ada penduduk dgn kk_level=1 dan id=nik_kepala untuk keluarga itu
  	$kk_kosong = $this->db->select('k.id')
  	  ->where('p.id is NULL')
  		->from('tweb_keluarga k')
  		->join('tweb_penduduk p', 'p.id = k.nik_kepala and p.kk_level = 1', 'left')
  		->get()->result_array();
  	foreach ($kk_kosong as $kk)
  	{
  		$this->db->where('id', $kk['id'])->update('tweb_keluarga', array('nik_kepala' => NULL));
  	}

		// Tambah surat keterangan domisili
		$data = array(
			'nama'=>'Keterangan Domisili',
			'url_surat'=>'surat_ket_domisili',
			'kode_surat'=>'S-41',
			'jenis'=>1);
		$sql = $this->db->insert_string('tweb_surat_format', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis)";
		$this->db->query($sql);

		$query = $this->db->select('1')->where('key', 'web_artikel_per_page')->get('setting_aplikasi');
		$query->result() OR	$this->db->insert('setting_aplikasi', array('key'=>'web_artikel_per_page', 'value'=>8, 'jenis'=>'int', 'keterangan'=>'Jumlah artikel dalam satu halaman', 'kategori'=>'web_theme'));

		$this->db->where('id', 42)->update('setting_modul', array('url'=>'modul/clear', 'aktif'=>'1'));

		// tambah setting penomoran_surat
		if ($this->setting->penomoran_surat == null)
		{
			$setting = $this->db->select('value')
			                    ->where('key', 'nomor_terakhir_semua_surat')
			                    ->get('setting_aplikasi')
			                    ->row();
			$this->db->insert(
				'setting_aplikasi',
				array(
					'key' => 'penomoran_surat',
					'value' => $setting->value ?: 2,
					'jenis' => 'option',
					'keterangan' => 'Penomoran surat mulai dari satu (1) setiap tahun'
				)
			);
			// Hapus setting nomor_terakhir_semua_surat
			$this->db->where('key', 'nomor_terakhir_semua_surat')->delete('setting_aplikasi');
		}

		$tb_option = 'setting_aplikasi_options';
		if (!$this->db->table_exists($tb_option))
		{
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 11,
					'unsigned' => FALSE,
					'auto_increment' => TRUE
				),
				'id_setting' => array(
					'type' => 'INT',
					'constraint' => 11,
					'unsigned' => FALSE
				),
				'value' => array(
					'type' => 'VARCHAR',
					'constraint' => 512
				)
			));
			$this->dbforge->add_key('id', true);
			$this->dbforge->create_table($tb_option, false, array('ENGINE' => $this->engine));
			$this->dbforge->add_column(
				$tb_option,
				array('CONSTRAINT `id_setting_fk` FOREIGN KEY (`id_setting`) REFERENCES `setting_aplikasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE')
			);
		}

		$set = $this->db->select('s.id,o.id oid')
		                ->where('key', 'penomoran_surat')
		                ->join("$tb_option o", 's.id=o.id_setting', 'LEFT')
		                ->get('setting_aplikasi s')
		                ->row();
		if (!$set->oid)
		{
			$this->db->insert_batch(
				$tb_option,
				array(
					array('id'=>1, 'id_setting'=>$set->id, 'value'=>'Nomor berurutan untuk masing-masing surat masuk dan keluar; dan untuk semua surat layanan'),
					array('id'=>2, 'id_setting'=>$set->id, 'value'=>'Nomor berurutan untuk masing-masing surat masuk dan keluar; dan untuk setiap surat layanan dengan jenis yang sama'),
					array('id'=>3, 'id_setting'=>$set->id, 'value'=>'Nomor berurutan untuk keseluruhan surat layanan, masuk dan keluar'),
				)
			);
		}
	}

  private function migrasi_1810_ke_1811()
  {
  	// Ubah url untuk Admin Web > Artikel, Admin Web > Dokumen, Admin Web > Menu,
  	// Admin Web > Komentar
		$this->db->where('id', 47)->update('setting_modul', array('url'=>'web/clear', 'aktif'=>'1'));
		$this->db->where('id', 52)->update('setting_modul', array('url'=>'dokumen/clear', 'aktif'=>'1'));
		$this->db->where('id', 50)->update('setting_modul', array('url'=>'komentar/clear', 'aktif'=>'1'));
		$this->db->where('id', 49)->update('setting_modul', array('url'=>'menu/clear', 'aktif'=>'1'));
		$this->db->where('id', 20)->update('setting_modul', array('url'=>'sid_core/clear', 'aktif'=>'1'));
  	// Ubah nama kolom 'nik' menjadi 'id_pend' dan hanya gunakan untuk pemilik desa
  	if ($this->db->field_exists('nik', 'data_persil'))
  	{
	  	$data = $this->db->select('d.*, d.nik as nama_pemilik, p.id as id_pend')
	  		->from('data_persil d')
	  		->join('tweb_penduduk p','p.nik = d.nik', 'left')
	  		->get()->result_array();
	  	foreach ($data as $persil)
	  	{
	  		$tulis = array();
	  		// Kalau pemilik luar pindahkan isi kolom 'nik' sebagai nama pemilik luar
	  		if ($persil['jenis_pemilik'] == 2 and empty($persil['pemilik_luar']))
	  		{
	  			$tulis['pemilik_luar'] = $persil['nama_pemilik'];
	  			$tulis['nik'] = NULL;
	  		}
	  		else
		  		// Untuk pemilik desa ganti menjadi id penduduk
	  			$tulis['nik'] = $persil['id_pend'];
	  		$this->db->where('id', $persil['id'])->update('data_persil', $tulis);
	  	}
	  	// Tambahkan relational constraint
		  $this->dbforge->modify_column('data_persil',
		  	array('nik' => array('name'  =>  'id_pend',	'type' => 'int', 'constraint' => 11 )));
			$this->db->query("ALTER TABLE `data_persil` ADD INDEX `id_pend` (`id_pend`)");
			$this->dbforge->add_column('data_persil', array(
	    	'CONSTRAINT `persil_pend_fk` FOREIGN KEY (`id_pend`) REFERENCES `tweb_penduduk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
			));
  	}
  	// Hapus kolom tweb_penduduk_mandiri.nik
		if ($this->db->field_exists('nik', 'tweb_penduduk_mandiri'))
		{
			$this->dbforge->drop_column('tweb_penduduk_mandiri', 'nik');
		}
		//menambahkan constraint kolom tabel
		$sql = "SELECT *
	    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
	    WHERE CONSTRAINT_NAME = 'id_pend_fk'
			AND TABLE_NAME = 'tweb_penduduk_mandiri'";
	  $query = $this->db->query($sql);
	  if ($query->num_rows() == 0)
	  {
			$this->dbforge->add_column('tweb_penduduk_mandiri', array(
	    	'CONSTRAINT `id_pend_fk` FOREIGN KEY (`id_pend`) REFERENCES `tweb_penduduk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
			));
	  }

  	// Tambah perubahan database di sini
		// Tambah setting tombol_cetak_surat
		$setting = $this->db->where('key','tombol_cetak_surat')->get('setting_aplikasi')->row()->id;
		if (!$setting)
		{
			$this->db->insert('setting_aplikasi', array('key'=>'tombol_cetak_surat', 'value'=>FALSE, 'jenis'=>'boolean', 'keterangan'=>'Tampilkan tombol cetak langsung di form surat'));
		}
  }

  private function migrasi_1809_ke_1810()
  {
		// Tambah tabel surat_keluar
		//Perbaiki url untuk modul Surat Keluar
		$this->db->where('id', 58)->update('setting_modul',array('url'=>'surat_keluar/clear', 'aktif'=>'1'));
		if (!$this->db->table_exists('surat_keluar') )
		{
			$query = "
				CREATE TABLE `surat_keluar` (
					`id` int NOT NULL AUTO_INCREMENT,
					`nomor_urut` smallint(5),
					`nomor_surat` varchar(20),
					`kode_surat` varchar(10),
					`tanggal_surat` date NOT NULL,
					`tanggal_catat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`tujuan` varchar(100),
					`isi_singkat` varchar(200),
					`berkas_scan` varchar(100),
					PRIMARY KEY  (`id`)
				);
			";
			$this->db->query($query);
		}

  	// Tambah klasifikasi surat
		if (!$this->db->table_exists('klasifikasi_surat') )
		{
			$data = array(
				'id' => '63',
				'modul' => 'Klasfikasi Surat',
				'url' => 'klasifikasi/clear',
				'aktif' => '1',
				'ikon' => 'fa-code',
				'urut' => '10',
				'level' => '2',
				'parent' => '15',
				'hidden' => '0',
				'ikon_kecil' => 'fa-code'
			);
			$sql = $this->db->insert_string('setting_modul', $data) . " ON DUPLICATE KEY UPDATE url=VALUES(url)";
			$this->db->query($sql);

			$query = "
			CREATE TABLE IF NOT EXISTS `klasifikasi_surat` (
			  `id` int(4) NOT NULL AUTO_INCREMENT,
			  `kode` varchar(50) NOT NULL,
			  `nama` varchar(250) NOT NULL,
			  `uraian` mediumtext NOT NULL,
				`enabled` int(2) NOT NULL DEFAULT '1',
			  PRIMARY KEY (`id`)
			)";
			$this->db->query($query);
			// Impor klasifikasi dari berkas csv
			$this->load->model('klasifikasi_model');
			$this->klasifikasi_model->impor(FCPATH . 'assets/import/klasifikasi_surat.csv');
		}

		//Perbaiki url untuk modul Surat Masuk dan Arsip Layanan
		$this->db->where('url','surat_masuk')->update('setting_modul',array('url'=>'surat_masuk/clear'));
		$this->db->where('url','keluar')->update('setting_modul',array('url'=>'keluar/clear'));
		//Perbaiki ikon untuk modul Sekretariat
		$this->db->where('url','sekretariat')->update('setting_modul',array('ikon'=>'fa-archive'));
		 // Buat view untuk penduduk hidup -- untuk memudahkan query
		if (!$this->db->table_exists('penduduk_hidup'))
			$this->db->query("CREATE VIEW penduduk_hidup AS SELECT * FROM tweb_penduduk WHERE status_dasar = 1");
		// update jenis pekerjaan PETANI/PERKEBUNAN ke 'PETANI/PEKEBUN'
		// sesuai dengan issue https://github.com/OpenSID/OpenSID/issues/999
		if ($this->db->table_exists('tweb_penduduk_pekerjaan'))
			$this->db->where('nama', 'PETANI/PERKEBUNAN')->update(
					'tweb_penduduk_pekerjaan',  array('nama' => 'PETANI/PEKEBUN'));
		// buat tabel disposisi dengan relasi ke surat masuk dan tweb_desa_pamong
		if (!$this->db->table_exists('disposisi_surat_masuk'))
		{
			$sql = array(
			  'id_disposisi'  =>  array(
				  'type' => 'INT',
				  'constraint' => 11,
				  'unsigned' => FALSE,
				  'auto_increment' => TRUE
				),
			  'id_surat_masuk'  =>  array(
				  'type' => 'INT',
				  'constraint' => 11,
				  'unsigned' => FALSE
				),
			  'id_desa_pamong'  =>  array(
				  'type' => 'INT',
				  'constraint' => 11,
				  'unsigned' => FALSE,
				  'null' => TRUE,
				),
			  'disposisi_ke' => array(
				  'type' => 'VARCHAR',
				  'constraint' => 50,
				  'null' => TRUE,
				)
			);
			$this->dbforge->add_field($sql);
			$this->dbforge->add_key("id_disposisi", TRUE);
			$this->dbforge->create_table('disposisi_surat_masuk', FALSE, array('ENGINE' => $this->engine));

			//menambahkan constraint kolom tabel
			$this->dbforge->add_column('disposisi_surat_masuk', array(
		    	'CONSTRAINT `id_surat_fk` FOREIGN KEY (`id_surat_masuk`) REFERENCES `surat_masuk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
		    	'CONSTRAINT `desa_pamong_fk` FOREIGN KEY (`id_desa_pamong`) REFERENCES `tweb_desa_pamong` (`pamong_id`) ON DELETE CASCADE ON UPDATE CASCADE'
			));

			if ($this->db->field_exists('disposisi_kepada', 'surat_masuk')) {

				// ambil semua data surat masuk
				$data = $this->db->select()->from('surat_masuk')->get()->result();

				// konversi data yang diperlukan
				// ke table disposisi_surat_masuk
				foreach ($data as $value)
				{
					$data_pamong = $this->db->select('pamong_id')
						->from('tweb_desa_pamong')
						->where('jabatan', $value->disposisi_kepada)
						->get()->row();

					$this->db->insert(
						'disposisi_surat_masuk', array(
							'id_surat_masuk' => $value->id,
							'id_desa_pamong' => $data_pamong->pamong_id,
							'disposisi_ke' => $value->disposisi_kepada
						)
					);
				}
				// hapus kolom disposisi dari surat masuk
				$this->dbforge->drop_column('surat_masuk','disposisi_kepada');
			}
		}
  }

  private function migrasi_1808_ke_1809()
  {
	// Hapus tabel inventaris lama
	$query = "DROP TABLE IF EXISTS mutasi_inventaris;";
	$this->db->query($query);
	$query = "DROP TABLE IF EXISTS inventaris;";
	$this->db->query($query);
	$query = "DROP TABLE IF EXISTS jenis_barang;";
	$this->db->query($query);

	// Siapkan warna polygon dan line supaya tampak di tampilan-admin baru
		$sql = "UPDATE polygon SET color = CONCAT('#', color)
				WHERE color NOT LIKE '#%' AND color <> ''
		";
		$this->db->query($sql);
		$sql = "UPDATE line SET color = CONCAT('#', color)
				WHERE color NOT LIKE '#%' AND color <> ''
		";
		$this->db->query($sql);

	// Tambahkan perubahan menu untuk tampilan-admin baru
	if (!$this->db->field_exists('parent', 'setting_modul') or strpos($this->getCurrentVersion(), '18.08') !== false)
	{
	  if (!$this->db->field_exists('parent', 'setting_modul'))
	  {
			$fields = array();
			$fields['parent'] = array(
				'type' => 'int',
				'constraint' => 2,
				'null' => FALSE,
				'default' => 0
			);
			$this->dbforge->add_column('setting_modul', $fields);
	  }

	  $this->db->truncate('setting_modul');
	  $query = "
		INSERT INTO setting_modul (`id`, `modul`, `url`, `aktif`, `ikon`, `urut`, `level`, `parent`, `hidden`, `ikon_kecil`) VALUES
		('1', 'Home', 'hom_sid', '1', 'fa-home', '1', '2', '0', '1', 'fa fa-home'),
		('200', 'Info [Desa]', 'hom_desa', '1', 'fa-dashboard', '2', '2', '0', '1', 'fa fa-home'),
		('2', 'Kependudukan', 'penduduk/clear', '1', 'fa-users', '3', '2', '0', '0', 'fa fa-users'),
		('3', 'Statistik', 'statistik', '1', 'fa-line-chart', '4', '2', '0', '0', 'fa fa-line-chart'),
		('4', 'Layanan Surat', 'surat', '1', 'fa-book', '5', '2', '0', '0', 'fa fa-book'),
		('5', 'Analisis', 'analisis_master/clear', '1', '   fa-check-square-o', '6', '2', '0', '0', 'fa fa-check-square-o'),
		('6', 'Bantuan', 'program_bantuan/clear', '1', 'fa-heart', '7', '2', '0', '0', 'fa fa-heart'),
		('7', 'Pertanahan', 'data_persil/clear', '1', 'fa-map-signs', '8', '2', '0', '0', 'fa fa-map-signs'),
		('8', 'Pengaturan Peta', 'plan', '1', 'fa-location-arrow', '9', '2', '9', '0', 'fa fa-location-arrow'),
		('9', 'Pemetaan', 'gis', '1', 'fa-globe', '10', '2', '0', '0', 'fa fa-globe'),
		('10', 'SMS', 'sms', '1', 'fa-envelope', '11', '2', '0', '0', 'fa fa-envelope'),
		('11', 'Pengaturan', 'man_user/clear', '1', 'fa-users', '12', '1', '0', '1', 'fa-users'),
		('13', 'Admin Web', 'web', '1', 'fa-desktop', '14', '4', '0', '0', 'fa fa-desktop'),
		('14', 'Layanan Mandiri', 'lapor', '1', 'fa-inbox', '15', '2', '0', '0', 'fa fa-inbox'),
		('15', 'Sekretariat', 'sekretariat', '1', 'fa-archive', '5', '2', '0', '0', 'fa fa-archive'),
		('16', 'SID', 'hom_sid', '1', 'fa-globe', '1', '2', '1', '0', ''),
		('17', 'Identitas [Desa]', 'hom_desa/konfigurasi', '1', 'fa-id-card', '2', '2', '200', '0', ''),
		('18', 'Pemerintahan [Desa]', 'pengurus', '1', 'fa-sitemap', '3', '2', '200', '0', ''),
		('19', 'Donasi', 'hom_sid/donasi', '1', 'fa-money', '4', '2', '1', '0', ''),
		('20', 'Wilayah Administratif', 'sid_core', '1', 'fa-map', '2', '2', '200', '0', ''),
		('21', 'Penduduk', 'penduduk/clear', '1', 'fa-user', '2', '2', '2', '0', ''),
		('22', 'Keluarga', 'keluarga/clear', '1', 'fa-users', '3', '2', '2', '0', ''),
		('23', 'Rumah Tangga', 'rtm/clear', '1', 'fa-venus-mars', '4', '2', '2', '0', ''),
		('24', 'Kelompok', 'kelompok/clear', '1', 'fa-sitemap', '5', '2', '2', '0', ''),
		('25', 'Data Suplemen', 'suplemen', '1', 'fa-slideshare', '6', '2', '2', '0', ''),
		('26', 'Calon Pemilih', 'dpt/clear', '1', 'fa-podcast', '7', '2', '2', '0', ''),
		('27', 'Statistik Kependudukan', 'statistik', '1', 'fa-bar-chart', '1', '2', '3', '0', ''),
		('28', 'Laporan Bulanan', 'laporan/clear', '1', 'fa-file-text', '2', '2', '3', '0', ''),
		('29', 'Laporan Kelompok Rentan', 'laporan_rentan/clear', '1', 'fa-wheelchair', '3', '2', '3', '0', ''),
		('30', 'Pengaturan Surat', 'surat_master/clear', '1', 'fa-cog', '1', '2', '4', '0', ''),
		('31', 'Cetak Surat', 'surat', '1', 'fa-files-o', '2', '2', '4', '0', ''),
		('32', 'Arsip Layanan', 'keluar', '1', 'fa-folder-open', '3', '2', '4', '0', ''),
		('33', 'Panduan', 'surat/panduan', '1', 'fa fa-book', '4', '2', '4', '0', ''),
		('39', 'SMS', 'sms', '1', 'fa-envelope-open-o', '1', '2', '10', '0', ''),
		('40', 'Daftar Kontak', 'sms/kontak', '1', 'fa-id-card-o', '2', '2', '10', '0', ''),
		('41', 'Pengaturan SMS', 'sms/setting', '1', 'fa-gear', '3', '2', '10', '0', ''),
		('42', 'Modul', 'modul', '1', 'fa-tags', '1', '1', '11', '0', ''),
		('43', 'Aplikasi', 'setting', '1', 'fa-codepen', '2', '1', '11', '0', ''),
		('44', 'Pengguna', 'man_user', '1', 'fa-users', '3', '1', '11', '0', ''),
		('45', 'Database', 'database', '1', 'fa-database', '4', '1', '11', '0', ''),
		('46', 'Info Sistem', 'setting/info_sistem', '1', 'fa-server', '5', '1', '11', '0', ''),
		('47', 'Artikel', 'web/index/1', '1', 'fa-file-movie-o', '1', '4', '13', '0', ''),
		('48', 'Widget', 'web_widget', '1', 'fa-windows', '2', '4', '13', '0', ''),
		('49', 'Menu', 'menu/index/1', '1', 'fa-bars', '3', '4', '13', '0', ''),
		('50', 'Komentar', 'komentar', '1', 'fa-comments', '4', '4', '13', '0', ''),
		('51', 'Galeri', 'gallery', '1', 'fa-image', '5', '5', '13', '0', ''),
		('52', 'Dokumen', 'dokumen', '1', 'fa-file-text', '6', '4', '13', '0', ''),
		('53', 'Media Sosial', 'sosmed', '1', 'fa-facebook', '7', '4', '13', '0', ''),
		('54', 'Slider', 'web/slider', '1', 'fa-film', '8', '4', '13', '0', ''),
		('55', 'Laporan Masuk', 'lapor', '1', 'fa-wechat', '1', '2', '14', '0', ''),
		('56', 'Pendaftar Layanan Mandiri', 'mandiri/clear', '1', 'fa-500px', '2', '2', '14', '0', ''),
		('57', 'Surat Masuk', 'surat_masuk', '1', 'fa-sign-in', '1', '2', '15', '0', ''),
		('58', 'Surat Keluar', '', '2', 'fa-sign-out', '2', '2', '15', '0', ''),
		('59', 'SK Kades', 'dokumen_sekretariat/index/2', '1', 'fa-legal', '3', '2', '15', '0', ''),
		('60', 'Perdes', 'dokumen_sekretariat/index/3', '1', 'fa-newspaper-o', '4', '2', '15', '0', ''),
		('61', 'Inventaris', 'inventaris_tanah', '1', 'fa-cubes', '5', '2', '15', '0', ''),
		('62', 'Peta', 'gis', '1', 'fa-globe', '1', '2', '9', '0', 'fa fa-globe');
	  ";
	  $this->db->query($query);
	}

	if ($this->db->table_exists('anggota_grup_kontak'))
		return;
	// Perubahan tabel untuk modul SMS
	// buat table anggota_grup_kontak
	$sql = array(
	  'id_grup_kontak'  =>  array(
		  'type' => 'INT',
		  'constraint' => 11,
		  'unsigned' => FALSE,
		  'auto_increment' => TRUE
		),
	  'id_grup'  =>  array(
		  'type' => 'INT',
		  'constraint' => 11,
		  'unsigned' => FALSE
		),
	  'id_kontak'  =>  array(
		  'type' => 'INT',
		  'constraint' => 11,
		  'unsigned' => FALSE
		)
	  );
	$this->dbforge->add_field($sql);
	$this->dbforge->add_key("id_grup_kontak", TRUE);
	$this->dbforge->create_table('anggota_grup_kontak', FALSE, array('ENGINE' => $this->engine));

	//perbaikan penamaan grup agar tidak ada html url code
	$this->db->query("UPDATE kontak_grup SET nama_grup = REPLACE(nama_grup, '%20', ' ')");
	//memindahkan isi kontak_grup ke anggota_grup_kontak
	$this->db->query("INSERT INTO anggota_grup_kontak (id_grup, id_kontak) SELECT b.id as id_grup, a.id_kontak FROM kontak_grup a RIGHT JOIN (SELECT id,nama_grup FROM kontak_grup GROUP BY nama_grup) b on a.nama_grup = b.nama_grup WHERE a.id_kontak <> 0");
	//Memperbaiki record kontak_grup agar tidak duplikat
	$this->db->query("DELETE t1 FROM kontak_grup t1 INNER JOIN kontak_grup t2  WHERE t1.id > t2.id AND t1.nama_grup = t2.nama_grup");

	//modifikasi tabel kontak dan kontak_grup
	if ($this->db->field_exists('id', 'kontak'))
	  $this->dbforge->modify_column('kontak', array('id' => array('name'  =>  'id_kontak', 'type' =>  'INT',  'auto_increment'  =>  TRUE )));
	if ($this->db->field_exists('id_kontak', 'kontak_grup'))
	  $this->dbforge->drop_column('kontak_grup', 'id_kontak');
	if ($this->db->field_exists('id', 'kontak_grup'))
	  $this->dbforge->modify_column('kontak_grup', array('id' => array('name'  =>  'id_grup', 'type' =>  'INT',  'auto_increment'  =>  TRUE )));

	//menambahkan constraint kolom tabel
	$this->dbforge->add_column('anggota_grup_kontak',array(
	  'CONSTRAINT `anggota_grup_kontak_ke_kontak` FOREIGN KEY (`id_kontak`) REFERENCES `kontak` (`id_kontak`) ON DELETE CASCADE ON UPDATE CASCADE',
	  'CONSTRAINT `anggota_grup_kontak_ke_kontak_grup` FOREIGN KEY (`id_grup`) REFERENCES `kontak_grup` (`id_grup`) ON DELETE CASCADE ON UPDATE CASCADE'
	));
	$this->dbforge->add_column('kontak',array(
	  'CONSTRAINT `kontak_ke_tweb_penduduk` FOREIGN KEY (`id_pend`) REFERENCES `tweb_penduduk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
	));
	//buat view
	$this->db->query("DROP VIEW IF EXISTS `daftar_kontak`");
	$this->db->query("CREATE VIEW `daftar_kontak` AS select `a`.`id_kontak` AS `id_kontak`,`a`.`id_pend` AS `id_pend`,`b`.`nama` AS `nama`,`a`.`no_hp` AS `no_hp`,(case when (`b`.`sex` = '1') then 'Laki-laki' else 'Perempuan' end) AS `sex`,`b`.`alamat_sekarang` AS `alamat_sekarang` from (`kontak` `a` left join `tweb_penduduk` `b` on((`a`.`id_pend` = `b`.`id`)))");
	$this->db->query("DROP VIEW IF EXISTS `daftar_grup`");
	$this->db->query("CREATE VIEW `daftar_grup` AS select `a`.*,(select count(`anggota_grup_kontak`.`id_kontak`) from `anggota_grup_kontak` where (`a`.`id_grup` = `anggota_grup_kontak`.`id_grup`)) AS `jumlah_anggota` from `kontak_grup` `a`");
	$this->db->query("DROP VIEW IF EXISTS `daftar_anggota_grup`");
	$this->db->query("CREATE VIEW `daftar_anggota_grup` AS select `a`.`id_grup_kontak` AS `id_grup_kontak`,`a`.`id_grup` AS `id_grup`,`c`.`nama_grup` AS `nama_grup`,`b`.`id_kontak` AS `id_kontak`,`b`.`nama` AS `nama`,`b`.`no_hp` AS `no_hp`,`b`.`sex` AS `sex`,`b`.`alamat_sekarang` AS `alamat_sekarang` from ((`anggota_grup_kontak` `a` left join `daftar_kontak` `b` on((`a`.`id_kontak` = `b`.`id_kontak`))) left join `kontak_grup` `c` on((`a`.`id_grup` = `c`.`id_grup`)))");

  }

  private function migrasi_1806_ke_1807()
  {
	// Tambahkan perubahan database di sini
	// Tambah kolom di tabel data_persil

		// Tambah wna_lk, wna_pr di log_bulanan
		// dan ubah lk menjadi wni_lk, dan pr menjadi wni_pr
		if (!$this->db->field_exists('wni_pr', 'log_bulanan'))
		{
			$fields = array();
			$fields['lk'] = array(
					'name' => 'wni_lk',
					'type' => 'int',
					'constraint' => 11
			);
			$fields['pr'] = array(
					'name' => 'wni_pr',
					'type' => 'int',
					'constraint' => 11
			);
			$this->dbforge->modify_column('log_bulanan', $fields);
			$fields = array();
			$fields['wna_lk'] = array(
					'type' => 'int',
					'constraint' => 11
			);
			$fields['wna_pr'] = array(
					'type' => 'int',
					'constraint' => 11
			);
			$this->dbforge->add_column('log_bulanan', $fields);
		}

		if (!$this->db->table_exists('inventaris_tanah') )
		{
			$query = "
			CREATE TABLE `inventaris_tanah` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nama_barang` varchar(255) NOT NULL,
				`kode_barang` varchar(64) NOT NULL,
				`register` varchar(64) NOT NULL,
				`luas` int(64) NOT NULL,
				`tahun_pengadaan` year(4) NOT NULL,
				`letak` varchar(255) NOT NULL,
				`hak` varchar(255) NOT NULL,
				`no_sertifikat` varchar(255) NOT NULL,
				`tanggal_sertifikat` date NOT NULL,
				`penggunaan` varchar(255) NOT NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('mutasi_inventaris_tanah') )
		{
			$query = "
			CREATE TABLE `mutasi_inventaris_tanah` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_inventaris_tanah` int(11),
				`jenis_mutasi` varchar(255) NOT NULL,
				`tahun_mutasi` date NOT NULL,
				`harga_jual` double NOT NULL,
				`sumbangkan` varchar(255) NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id),
				CONSTRAINT FK_mutasi_inventaris_tanah FOREIGN KEY (id_inventaris_tanah) REFERENCES inventaris_tanah(id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('inventaris_peralatan') )
		{
			$query = "
			CREATE TABLE `inventaris_peralatan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nama_barang` varchar(255) NOT NULL,
				`kode_barang` varchar(64) NOT NULL,
				`register` varchar(64) NOT NULL,
				`merk` varchar(255) NOT NULL,
				`ukuran`text NOT NULL,
				`bahan` text NOT NULL,
				`tahun_pengadaan` year(4) NOT NULL,
				`no_pabrik` varchar(255) NULL,
				`no_rangka` varchar(255) NULL,
				`no_mesin` varchar(255) NULL,
				`no_polisi` varchar(255) NULL,
				`no_bpkb` varchar(255) NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('mutasi_inventaris_peralatan') )
		{
			$query = "
			CREATE TABLE `mutasi_inventaris_peralatan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_inventaris_peralatan` int(11),
				`jenis_mutasi` varchar(255) NOT NULL,
				`tahun_mutasi` date NOT NULL,
				`harga_jual` double NOT NULL,
				`sumbangkan` varchar(255) NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id),
				CONSTRAINT FK_mutasi_inventaris_peralatan FOREIGN KEY (id_inventaris_peralatan) REFERENCES inventaris_peralatan(id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('inventaris_gedung') )
		{
			$query = "
			CREATE TABLE `inventaris_gedung` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nama_barang` varchar(255) NOT NULL,
				`kode_barang` varchar(64) NOT NULL,
				`register` varchar(64) NOT NULL,
				`kondisi_bangunan` varchar(255) NOT NULL,
				`kontruksi_bertingkat` varchar(255) NOT NULL,
				`kontruksi_beton` int(1) NOT NULL,
				`luas_bangunan` int(64) NOT NULL,
				`letak` varchar(255) NOT NULL,
				`tanggal_dokument`DATE NULL,
				`no_dokument` varchar(255) NULL,
				`luas` int(64) NULL,
				`status_tanah` varchar(255) NULL,
				`kode_tanah` varchar(255) NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('mutasi_inventaris_gedung') )
		{
			$query = "
			CREATE TABLE `mutasi_inventaris_gedung` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_inventaris_gedung` int(11),
				`jenis_mutasi` varchar(255) NOT NULL,
				`tahun_mutasi` date NOT NULL,
				`harga_jual` double NOT NULL,
				`sumbangkan` varchar(255) NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id),
				CONSTRAINT FK_mutasi_inventaris_gedung FOREIGN KEY (id_inventaris_gedung) REFERENCES inventaris_gedung(id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('inventaris_jalan') )
		{
			$query = "
			CREATE TABLE `inventaris_jalan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nama_barang` varchar(255) NOT NULL,
				`kode_barang` varchar(64) NOT NULL,
				`register` varchar(64) NOT NULL,
				`kontruksi` varchar(255) NOT NULL,
				`panjang` int(64) NOT NULL,
				`lebar`int(64) NOT NULL,
				`luas` int(64) NOT NULL,
				`letak` text NULL,
				`tanggal_dokument` date NOT NULL,
				`no_dokument` varchar(255) DEFAULT NULL,
				`status_tanah` varchar(255) DEFAULT NULL,
				`kode_tanah` varchar(255) DEFAULT NULL,
				`kondisi` varchar(255) NOT NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('mutasi_inventaris_jalan') )
		{
			$query = "
			CREATE TABLE `mutasi_inventaris_jalan` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_inventaris_jalan` int(11),
				`jenis_mutasi` varchar(255) NOT NULL,
				`tahun_mutasi` date NOT NULL,
				`harga_jual` double NOT NULL,
				`sumbangkan` varchar(255) NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id),
				CONSTRAINT FK_mutasi_inventaris_jalan FOREIGN KEY (id_inventaris_jalan) REFERENCES inventaris_jalan(id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('inventaris_asset') )
		{
			$query = "
			CREATE TABLE `inventaris_asset` (
				`id` int(11) AUTO_INCREMENT NOT NULL,
				`nama_barang` varchar(255) NOT NULL,
				`kode_barang` varchar(64) NOT NULL,
				`register` varchar(64) NOT NULL,
				`jenis` varchar(255) NOT NULL,
				`judul_buku` varchar(255) NULL,
				`spesifikasi_buku` varchar(255) NULL,
				`asal_daerah` varchar(255) NULL,
				`pencipta` varchar(255) NULL,
				`bahan` varchar(255) NULL,
				`jenis_hewan` varchar(255) NULL,
				`ukuran_hewan` varchar(255) NULL,
				`jenis_tumbuhan` varchar(255) NULL,
				`ukuran_tumbuhan` varchar(255) NULL,
				`jumlah` int(64) NOT NULL,
				`tahun_pengadaan` year(4) NOT NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('mutasi_inventaris_asset') )
		{
			$query = "
			CREATE TABLE `mutasi_inventaris_asset` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`id_inventaris_asset` int(11),
				`jenis_mutasi` varchar(255) NOT NULL,
				`tahun_mutasi` date NOT NULL,
				`harga_jual` double NOT NULL,
				`sumbangkan` varchar(255) NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id),
				CONSTRAINT FK_mutasi_inventaris_asset FOREIGN KEY (id_inventaris_asset) REFERENCES inventaris_asset(id)
			)
			";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('inventaris_kontruksi') )
		{
			$query = "
			CREATE TABLE `inventaris_kontruksi` (
				`id` int(11) AUTO_INCREMENT NOT NULL ,
				`nama_barang` varchar(255) NOT NULL,
				`kondisi_bangunan` varchar(255) NOT NULL,
				`kontruksi_bertingkat` varchar(255) NOT NULL,
				`kontruksi_beton` int(1) NOT NULL,
				`luas_bangunan` int(64) NOT NULL,
				`letak` varchar(255) NOT NULL,
				`tanggal_dokument` date DEFAULT NULL,
				`no_dokument` varchar(255) DEFAULT NULL,
				`tanggal` date DEFAULT NULL,
				`status_tanah` varchar(255) DEFAULT NULL,
				`kode_tanah` varchar(255) DEFAULT NULL,
				`asal` varchar(255) NOT NULL,
				`harga` double NOT NULL,
				`keterangan` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`created_by` int(11) NOT NULL,
				`updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
				`updated_by` int(11) NOT NULL,
				`status` int(1) NOT NULL DEFAULT '0',
				`visible` int(1) NOT NULL DEFAULT '1',
				PRIMARY KEY (id)
			)
			";
			$this->db->query($query);
		}

		$fields = array();
		if (!$this->db->field_exists('jenis_pemilik', 'data_persil'))
		{
			$fields['jenis_pemilik'] = array(
					'type' => 'tinyint',
					'constraint' => 2,
					'null' => FALSE,
					'default' => 1 // pemilik desa
			);
		}
		if (!$this->db->field_exists('pemilik_luar', 'data_persil'))
		{
			$fields['pemilik_luar'] = array(
					'type' => 'varchar',
					'constraint' => 100
			);
		}
		$this->dbforge->add_column('data_persil', $fields);
		// Sesuaikan data pemilik luar desa yg sudah ada ke kolom baru
		if (count($fields) > 0)
		{
			$data = $this->db->get('data_persil')->result_array();
			foreach ($data as $persil)
			{
				if (!is_numeric($persil['nik']) AND $persil['nik']<>'')
				{
					$data_update = array(
						'jenis_pemilik' => '2',
						'pemilik_luar' => $persil['nik'],
						'nik' => 999   // NIK_LUAR_DESA
					);
					$this->db->where('id', $persil['id'])->update('data_persil', $data_update);
				}
			}
		}
		if ($this->db->field_exists('alamat_ext', 'data_persil'))
		{
			$fields = array();
			$fields['alamat_ext'] = array(
					'name' => 'alamat_luar',
					'type' => 'varchar',
					'constraint' => 100
			);
			$this->dbforge->modify_column('data_persil', $fields);
		}

	}

	private function migrasi_211_ke_1806()
	{
		//ambil nilai path
		$config = $this->db->get('config')->row();
		if (!empty($config))
		{
			//Cek apakah path kosong atau tidak
			if (!empty($config->path))
			{
				//Cek pola path yang lama untuk diganti dengan yang baru
				//Jika pola path masih yang lama, ganti dengan yang baru
				if (preg_match('/((\([-+]?[0-9]{1,3}\.[0-9]*,(\s)?[-+]?[0-9]{1,3}\.[0-9]*\))\;)/', $config->path))
				{
					$new_path = str_replace(array(');', '(', '][' ), array(']','[','],['), $config->path);
				 $this->db->where('id', $config->id)->update('config', array('path' => "[[$new_path]]"));
				}
			}
			//Cek zoom agar tidak lebih dari 18 dan agar tidak kosong
			if(empty($config->zoom) || $config->zoom > 18 || $config->zoom == 0){
					$this->db->where('id', $config->id)->update('config', array('zoom' => 10));
			}
		}

		//Penambahan widget peta wilayah desa
		$widget = $this->db->select('id, isi')->where('isi', 'peta_wilayah_desa.php')->get('widget')->row();
		if (empty($widget))
		{
			//Penambahan widget peta wilayah desa sebagai widget sistem
			$peta_wilayah = array(
				'isi'           => 'peta_wilayah_desa.php',
				'enabled'       => 1,
				'judul'         => 'Peta Wilayah Desa',
				'jenis_widget'  => 1,
				'urut'          => 1,
				'form_admin'    => 'hom_desa/konfigurasi'
			);
			$this->db->insert('widget', $peta_wilayah);
		}
		else
		{
			// Paksa update karena sudah ada yang menggunakan versi pra-rilis sebelumnya
			$this->db->where('id', $widget->id)
				->update('widget', array('form_admin' => 'hom_desa/konfigurasi'));
		}

		//ubah icon kecil dan besar untuk modul Sekretariat
		$this->db->where('url','sekretariat')->update('setting_modul',array('ikon'=>'document-open-8.png', 'ikon_kecil'=>'fa fa-file fa-lg'));
		 // Hapus kolom yg tidak digunakan
		if ($this->db->field_exists('alamat_tempat_lahir', 'tweb_penduduk'))
			$this->dbforge->drop_column('tweb_penduduk', 'alamat_tempat_lahir');
	}

	private function migrasi_210_ke_211()
	{
		// Tambah kolom jenis untuk analisis_master
		$fields = array();
		if (!$this->db->field_exists('jenis', 'analisis_master'))
		{
			$fields['jenis'] = array(
					'type' => 'tinyint',
					'constraint' => 2,
					'null' => FALSE,
					'default' => 2 // bukan bawaan sistem
			);
		}
		$this->dbforge->add_column('analisis_master', $fields);
		// Impor analisis Data Dasar Keluarga kalau belum ada.
		// Ubah versi pra-rilis yang sudah diganti menjadi non-sistem
		$ddk_lama = $this->db->where('kode_analisis', 'DDKPD')->where('jenis', 1)
			->get('analisis_master')->row();
		if ($ddk_lama)
		{
			$this->db->where('id',$ddk_lama->id)
			->update('analisis_master',array('jenis' => 2, 'nama' => '[kadaluarsa] '.$ddk_lama->nama));
		}
		$query = $this->db->where('kode_analisis', 'DDK02')
			->get('analisis_master')->result_array();
		if (count($query) == 0)
		{
			$file_analisis = FCPATH . 'assets/import/analisis_DDK_Profil_Desa.xls';
			$this->analisis_import_model->import_excel($file_analisis,'DDK02',$jenis = 1);
		}
		// Impor analisis Data Anggota Keluarga kalau belum ada
		// Ubah versi pra-rilis yang sudah diganti menjadi non-sistem
		$dak_lama = $this->db->where('kode_analisis','DAKPD')->where('jenis', 1)
			->get('analisis_master')->row();
		if ($dak_lama)
		{
			$this->db->where('id',$dak_lama->id)
			->update('analisis_master',array('jenis' => 2, 'nama' => '[kadaluarsa] '.$dak_lama->nama));
		}
		$dak = $this->db->where('kode_analisis', 'DAK02')
			->get('analisis_master')->row();
		if (empty($dak))
		{
			$file_analisis = FCPATH . 'assets/import/analisis_DAK_Profil_Desa.xls';
			$id_dak = $this->analisis_import_model->import_excel($file_analisis,'DAK02', $jenis = 1);
		} else $id_dak = $dak->id;
		// Tambah kolom is_teks pada analisis_indikator
		$fields = array();
		if (!$this->db->field_exists('is_teks', 'analisis_indikator'))
		{
			$fields['is_teks'] = array(
					'type' => 'tinyint',
					'constraint' => 1,
					'null' => FALSE,
					'default' => 0 // isian pertanyaan menggunakan kode
			);
		}
		$this->dbforge->add_column('analisis_indikator', $fields);
		// Ubah pertanyaan2 DAK profil desa menggunakan teks
		$pertanyaan = array(
			'Cacat Fisik',
			'Cacat Mental',
			'Kedudukan Anggota Keluarga sebagai Wajib Pajak dan Retribusi',
			'Lembaga Pemerintahan Yang Diikuti Anggota Keluarga',
			'Lembaga Kemasyarakatan Yang Diikuti Anggota Keluarga',
			'Lembaga Ekonomi Yang Dimiliki Anggota Keluarga'
		);
		$list_pertanyaan = sql_in_list($pertanyaan);
		$this->db->where('id_master',$id_dak)->where("pertanyaan in($list_pertanyaan)")
			->update('analisis_indikator',array('is_teks' => 1));
	}

	private function migrasi_29_ke_210()
	{
		// Tambah kolom untuk format impor respon untuk analisis_master
			$fields = array();
			if (!$this->db->field_exists('format_impor', 'analisis_master'))
			{
				$fields['format_impor'] = array(
						'type' => 'tinyint',
						'constraint' => 2
				);
			}
			$this->dbforge->add_column('analisis_master', $fields);
		// Tambah setting timezone
		$setting = $this->db->where('key','timezone')->get('setting_aplikasi')->row()->id;
		if (!$setting)
		{
			$this->db->insert('setting_aplikasi',array('key'=>'timezone','value'=>'Asia/Jakarta','keterangan'=>'Zona waktu perekaman waktu dan tanggal'));
		}
		// Tambah tabel inventaris
		if (!$this->db->table_exists('jenis_barang') )
		{
			$query = "
				CREATE TABLE jenis_barang (
					id int NOT NULL AUTO_INCREMENT,
					nama varchar(30),
					keterangan varchar(100),
					PRIMARY KEY (id)
				);
			";
			$this->db->query($query);
		}
		if (!$this->db->table_exists('inventaris') )
		{
			$query = "
				CREATE TABLE inventaris (
					id int NOT NULL AUTO_INCREMENT,
					id_jenis_barang int(6),
					asal_sendiri int(6),
					asal_pemerintah int(6),
					asal_provinsi int(6),
					asal_kab int(6),
					asal_sumbangan int(6),
					hapus_rusak int(6),
					hapus_dijual int(6),
					hapus_sumbangkan int(6),
					tanggal_mutasi date NOT NULL,
					jenis_mutasi int(6),
					keterangan varchar(100),
					PRIMARY KEY (id),
					FOREIGN KEY (id_jenis_barang)
						REFERENCES jenis_barang(id)
						ON DELETE CASCADE
				);
			";
			$this->db->query($query);
		}
		// Perubahan pada pra-rilis
		// Hapus kolom
		$daftar_kolom = array('asal_sendiri','asal_pemerintah','asal_provinsi','asal_kab','asal_sumbangan','tanggal_mutasi','jenis_mutasi','hapus_rusak','hapus_dijual','hapus_sumbangkan');
		foreach ($daftar_kolom as $kolom)
		{
			if ($this->db->field_exists($kolom, 'inventaris'))
				$this->dbforge->drop_column('inventaris', $kolom);
		}
		// Tambah kolom
		$fields = array();
		if (!$this->db->field_exists('tanggal_pengadaan', 'inventaris'))
		{
			$fields['tanggal_pengadaan'] = array(
					'type' => 'date',
					'null' => FALSE
			);
		}
		if (!$this->db->field_exists('nama_barang', 'inventaris'))
		{
			$fields['nama_barang'] = array(
					'type' => 'VARCHAR',
					'constraint' => 100
			);
		}
		if (!$this->db->field_exists('asal_barang', 'inventaris'))
		{
			$fields['asal_barang'] = array(
					'type' => 'tinyint',
					'constraint' => 2
			);
		}
		if (!$this->db->field_exists('jml_barang', 'inventaris'))
		{
			$fields['jml_barang'] = array(
					'type' => 'int',
					'constraint' => 6
			);
		}
		$this->dbforge->add_column('inventaris', $fields);
		if (!$this->db->table_exists('mutasi_inventaris') )
		{
			$query = "
				CREATE TABLE mutasi_inventaris (
					id int NOT NULL AUTO_INCREMENT,
					id_barang int(6),
					tanggal_mutasi date NOT NULL,
					jenis_mutasi tinyint(2),
					jenis_penghapusan tinyint(2),
					jml_mutasi int(6),
					keterangan varchar(100),
					PRIMARY KEY (id),
					FOREIGN KEY (id_barang)
						REFERENCES inventaris(id)
						ON DELETE CASCADE
				);
			";
			$this->db->query($query);
		}
		// Ubah url modul program_bantuan
		$this->db->where('url','program_bantuan')->update('setting_modul',array('url'=>'program_bantuan/clear'));
	}

	private function migrasi_28_ke_29()
	{
		// Tambah data kelahiran ke tweb_penduduk
		$fields = array();
		if (!$this->db->field_exists('waktu_lahir', 'tweb_penduduk'))
		{
			$fields['waktu_lahir'] = array(
					'type' => 'VARCHAR',
					'constraint' => 5
			);
		}
		if (!$this->db->field_exists('tempat_dilahirkan', 'tweb_penduduk'))
		{
			$fields['tempat_dilahirkan'] = array(
					'type' => 'tinyint',
					'constraint' => 2
			);
		}
		if (!$this->db->field_exists('alamat_tempat_lahir', 'tweb_penduduk'))
		{
			$fields['alamat_tempat_lahir'] = array(
					'type' => 'VARCHAR',
					'constraint' => 100
			);
		}
		if (!$this->db->field_exists('jenis_kelahiran', 'tweb_penduduk'))
		{
			$fields['jenis_kelahiran'] = array(
					'type' => 'tinyint',
					'constraint' => 2
			);
		}
		if (!$this->db->field_exists('kelahiran_anak_ke', 'tweb_penduduk'))
		{
			$fields['kelahiran_anak_ke'] = array(
					'type' => 'tinyint',
					'constraint' => 2
			);
		}
		if (!$this->db->field_exists('penolong_kelahiran', 'tweb_penduduk'))
		{
			$fields['penolong_kelahiran'] = array(
					'type' => 'tinyint',
					'constraint' => 2
			);
		}
		if (!$this->db->field_exists('berat_lahir', 'tweb_penduduk'))
		{
			$fields['berat_lahir'] = array(
					'type' => 'varchar',
					'constraint' => 10
			);
		}
		if (!$this->db->field_exists('panjang_lahir', 'tweb_penduduk'))
		{
			$fields['panjang_lahir'] = array(
					'type' => 'varchar',
					'constraint' => 10
			);
		}
		$this->dbforge->add_column('tweb_penduduk', $fields);

		// Hapus kolom yg tidak digunakan
		if ($this->db->field_exists('pendidikan_id', 'tweb_penduduk'))
			$this->dbforge->drop_column('tweb_penduduk', 'pendidikan_id');
		// Tambah kolom e-ktp di tabel tweb_penduduk
		if (!$this->db->field_exists('ktp_el', 'tweb_penduduk'))
		{
			$fields = array(
				'ktp_el' => array(
					'type' => tinyint,
					'constraint' => 4
				)
			);
			$this->dbforge->add_column('tweb_penduduk', $fields);
		}
		if (!$this->db->field_exists('status_rekam', 'tweb_penduduk'))
		{
			$fields = array(
				'status_rekam' => array(
					'type' => tinyint,
					'constraint' => 4,
					'null' => FALSE,
					'default' => 0
				)
			);
			$this->dbforge->add_column('tweb_penduduk', $fields);
		}
		 // Tambah tabel status_rekam
		$query = "DROP TABLE IF EXISTS tweb_status_ktp;";
		$this->db->query($query);

		$query = "
			CREATE TABLE tweb_status_ktp (
				id tinyint(5) NOT NULL AUTO_INCREMENT,
				nama varchar(50) NOT NULL,
				ktp_el tinyint(4) NOT NULL,
				status_rekam varchar(50) NOT NULL,
				PRIMARY KEY (id)
			) ENGINE=".$this->engine." AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
		";
		$this->db->query($query);

		$query = "
			INSERT INTO tweb_status_ktp (id, nama, ktp_el, status_rekam) VALUES
			(1, 'BELUM REKAM', 1, '2'),
			(2, 'SUDAH REKAM', 2, '3'),
			(3, 'CARD PRINTED', 2, '4'),
			(4, 'PRINT READY RECORD', 2 ,'5'),
			(5, 'CARD SHIPPED', 2, '6'),
			(6, 'SENT FOR CARD PRINTING', 2, '7'),
			(7, 'CARD ISSUED', 2, '8');
		";
		$this->db->query($query);
	}

	private function migrasi_27_ke_28()
	{
		if (!$this->db->table_exists('suplemen') )
		{
			$query = "
				CREATE TABLE suplemen (
					id int NOT NULL AUTO_INCREMENT,
					nama varchar(100),
					sasaran tinyint(4),
					keterangan varchar(300),
					PRIMARY KEY (id)
				);
			";
			$this->db->query($query);
		}
		if (!$this->db->table_exists('suplemen_terdata') )
		{
			$query = "
				CREATE TABLE suplemen_terdata (
					id int NOT NULL AUTO_INCREMENT,
					id_suplemen int(10),
					id_terdata varchar(20),
					sasaran tinyint(4),
					keterangan varchar(100),
					PRIMARY KEY (id),
					FOREIGN KEY (id_suplemen)
						REFERENCES suplemen(id)
						ON DELETE CASCADE
				);
			";
			$this->db->query($query);
		}
		// Hapus surat permohonan perubahan kk (yang telah diubah menjadi kartu keluarga)
		$data = array(
			'nama'=>'Permohonan Perubahan Kartu Keluarga',
			'url_surat'=>'surat_permohonan_perubahan_kartu_keluarga',
			'kode_surat'=>'S-41',
			'lampiran'=>'f-1.16.php,f-1.01.php',
			'jenis'=>1);
		$hasil = $this->db->where('url_surat','surat_permohonan_perubahan_kk')->get('tweb_surat_format');
		if ($hasil->num_rows() > 0)
		{
			$this->db->where('url_surat','surat_permohonan_perubahan_kk')->update('tweb_surat_format', $data);
		}
		else
		{
			// Tambah surat permohonan perubahan kartu keluarga
			$sql = $this->db->insert_string('tweb_surat_format', $data);
			$sql .= " ON DUPLICATE KEY UPDATE
					nama = VALUES(nama),
					url_surat = VALUES(url_surat),
					kode_surat = VALUES(kode_surat),
					lampiran = VALUES(lampiran),
					jenis = VALUES(jenis)";
			$this->db->query($sql);
		}
	}

	private function migrasi_26_ke_27()
	{
		// Sesuaikan judul kelompok umur dengan SID 3.10 versi Okt 2017
		$this->db->truncate('tweb_penduduk_umur');
		$sql = '
			INSERT INTO tweb_penduduk_umur VALUES
			("1","BALITA","0","5","0"),
			("2","ANAK-ANAK","6","17","0"),
			("3","DEWASA","18","30","0"),
			("4","TUA","31","120","0"),
			("6","Di bawah 1 Tahun","0","1","1"),
			("9","2 s/d 4 Tahun","2","4","1"),
			("12","5 s/d 9 Tahun","5","9","1"),
			("13","10 s/d 14 Tahun","10","14","1"),
			("14","15 s/d 19 Tahun","15","19","1"),
			("15","20 s/d 24 Tahun","20","24","1"),
			("16","25 s/d 29 Tahun","25","29","1"),
			("17","30 s/d 34 Tahun","30","34","1"),
			("18","35 s/d 39 Tahun ","35","39","1"),
			("19","40 s/d 44 Tahun","40","44","1"),
			("20","45 s/d 49 Tahun","45","49","1"),
			("21","50 s/d 54 Tahun","50","54","1"),
			("22","55 s/d 59 Tahun","55","59","1"),
			("23","60 s/d 64 Tahun","60","64","1"),
			("24","65 s/d 69 Tahun","65","69","1"),
			("25","70 s/d 74 Tahun","70","74","1"),
			("26","Di atas 75 Tahun","75","99999","1");
		';
		$this->db->query($sql);
		// Tambah tombol media sosial Instagram
		$query = "
			INSERT INTO media_sosial (id, gambar, link, nama, enabled) VALUES ('5', 'ins.png', '', 'Instagram', '1')
			ON DUPLICATE KEY UPDATE
				gambar = VALUES(gambar),
				nama = VALUES(nama)";
		$this->db->query($query);
		// Ganti kelas sosial dengan tingkatan keluarga sejahtera dari BKKBN
		if ($this->db->table_exists('ref_kelas_sosial') )
		{
			$this->dbforge->drop_table('ref_kelas_sosial');
		}
		if (!$this->db->table_exists('tweb_keluarga_sejahtera') )
		{
			$query = "
				CREATE TABLE `tweb_keluarga_sejahtera` (
					`id` int(10),
					`nama` varchar(100),
					PRIMARY KEY  (`id`)
				);
			";
			$this->db->query($query);
			$query = "
				INSERT INTO `tweb_keluarga_sejahtera` (`id`, `nama`) VALUES
				(1,  'Keluarga Pra Sejahtera'),
				(2,  'Keluarga Sejahtera I'),
				(3,  'Keluarga Sejahtera II'),
				(4,  'Keluarga Sejahtera III'),
				(5,  'Keluarga Sejahtera III Plus')
			";
			$this->db->query($query);
		}
		// Tambah surat izin orang tua/suami/istri
		$data = array(
			'nama'=>'Keterangan Izin Orang Tua/Suami/Istri',
			'url_surat'=>'surat_izin_orangtua_suami_istri',
			'kode_surat'=>'S-39',
			'jenis'=>1);
		$sql = $this->db->insert_string('tweb_surat_format', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis)";
		$this->db->query($sql);
		// Tambah surat sporadik
		$data = array(
			'nama'=>'Pernyataan Penguasaan Fisik Bidang Tanah (SPORADIK)',
			'url_surat'=>'surat_sporadik',
			'kode_surat'=>'S-40',
			'jenis'=>1);
		$sql = $this->db->insert_string('tweb_surat_format', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis)";
		$this->db->query($sql);
	}

	private function migrasi_25_ke_26()
	{
		// Tambah tabel provinsi
		if (!$this->db->table_exists('provinsi') )
		{
			$query = "
				CREATE TABLE `provinsi` (
					`kode` tinyint(2),
					`nama` varchar(100),
					PRIMARY KEY  (`kode`)
				);
			";
			$this->db->query($query);
			$query = "
				INSERT INTO `provinsi` (`kode`, `nama`) VALUES
				(11,  'Aceh'),
				(12,  'Sumatera Utara'),
				(13,  'Sumatera Barat'),
				(14,  'Riau'),
				(15,  'Jambi'),
				(16,  'Sumatera Selatan'),
				(17,  'Bengkulu'),
				(18,  'Lampung'),
				(19,  'Kepulauan Bangka Belitung'),
				(21,  'Kepulauan Riau'),
				(31,  'DKI Jakarta'),
				(32,  'Jawa Barat'),
				(33,  'Jawa Tengah'),
				(34,  'DI Yogyakarta'),
				(35,  'Jawa Timur'),
				(36,  'Banten'),
				(51,  'Bali'),
				(52,  'Nusa Tenggara Barat'),
				(53,  'Nusa Tenggara Timur'),
				(61,  'Kalimantan Barat'),
				(62,  'Kalimantan Tengah'),
				(63,  'Kalimantan Selatan'),
				(64,  'Kalimantan Timur'),
				(65,  'Kalimantan Utara'),
				(71,  'Sulawesi Utara'),
				(72,  'Sulawesi Tengah'),
				(73,  'Sulawesi Selatan'),
				(74,  'Sulawesi Tenggara'),
				(75,  'Gorontalo'),
				(76,  'Sulawesi Barat'),
				(81,  'Maluku'),
				(82,  'Maluku Utara'),
				(91,  'Papua'),
				(92,  'Papua Barat')
			";
			$this->db->query($query);
		}
		// Konversi nama provinsi tersimpan di identitas desa
		$konversi = array(
			"ntb" => "Nusa Tenggara Barat",
			"ntt" => "Nusa Tenggara Timur",
			"daerah istimewa yogyakarta" => "DI Yogyakarta",
			"diy" => "DI Yogyakarta",
			"yogyakarta" => "DI Yogyakarta",
			"jabar" => "Jawa Barat",
			"jawabarat" => "Jawa Barat",
			"jateng" => "Jawa Tengah",
			"jatim" => "Jawa Timur",
			"jatimi" => "Jawa Timur",
			"jawa timu" => "Jawa Timur",
			"nad" => "Aceh",
			"kalimatnan barat" => "Kalimantan Barat",
			"sulawesi teanggara" => "Sulawesi Tenggara"
		);
		$nama_propinsi = $this->db->select('nama_propinsi')->where('id', '1')->get('config')->row()->nama_propinsi;
		foreach ($konversi as $salah => $benar) {
			if(strtolower($nama_propinsi) == $salah) {
				$this->db->where('id', '1')->update('config', array('nama_propinsi' => $benar));
				break;
			}
		}
		// Tambah lampiran untuk Surat Keterangan Kematian
		$this->db->where('url_surat','surat_ket_kematian')->update('tweb_surat_format', array('lampiran'=>'f-2.29.php'));
		// Ubah nama lampiran untuk Surat Keterangan Kelahiran
		$this->db->where('url_surat','surat_ket_kelahiran')->update('tweb_surat_format', array('lampiran'=>'f-2.01.php'));
		// Tambah modul Sekretariat di urutan sesudah Cetak Surat
		$list_modul = array(
			"5"  => 6,    // Analisis
			"6"  => 7,    // Bantuan
			"7"  => 8,    // Persil
			"8"  => 9,    // Plan
			"9"  => 10,   // Peta
			"10" => 11,   // SMS
			"11" => 12,   // Pengguna
			"12" => 13,   // Database
			"13" => 14,   // Admin Web
			"14" => 15);  // Laporan
		foreach ($list_modul as $key => $value)
		{
			$this->db->where('id',$key)->update('setting_modul', array('urut' => $value));
		}
		$query = "
			INSERT INTO setting_modul (id, modul, url, aktif, ikon, urut, level, hidden, ikon_kecil) VALUES
			('15','Sekretariat','sekretariat','1','applications-office-5.png','5','2','0','fa fa-print fa-lg')
			ON DUPLICATE KEY UPDATE
				modul = VALUES(modul),
				url = VALUES(url)";
		$this->db->query($query);
		// Tambah folder desa/upload/media
		if (!file_exists('/desa/upload/media'))
		{
			mkdir('desa/upload/media');
			xcopy('desa-contoh/upload/media', 'desa/upload/media');
		}
		if (!file_exists('/desa/upload/thumbs'))
		{
			mkdir('desa/upload/thumbs');
			xcopy('desa-contoh/upload/thumbs', 'desa/upload/thumbs');
		}
		// Tambah kolom kode di tabel kelompok
		if (!$this->db->field_exists('kode', 'kelompok'))
		{
			$fields = array(
				'kode' => array(
					'type' => 'VARCHAR',
					'constraint' => 16,
					'null' => FALSE
				)
			);
			$this->dbforge->add_column('kelompok', $fields);
		}
		// Tambah kolom no_anggota di tabel kelompok_anggota
		if (!$this->db->field_exists('no_anggota', 'kelompok_anggota'))
		{
			$fields = array(
				'no_anggota' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'null' => FALSE
				)
			);
			$this->dbforge->add_column('kelompok_anggota', $fields);
		}
	}

	private function migrasi_24_ke_25()
	{
		// Tambah setting current_version untuk migrasi
		$setting = $this->db->where('key','current_version')->get('setting_aplikasi')->row()->id;
		if (!$setting)
		{
			$this->db->insert('setting_aplikasi',array('key'=>'current_version','value'=>'2.4','keterangan'=>'Versi sekarang untuk migrasi'));
		}
		// Tambah kolom ikon_kecil di tabel setting_modul
		if (!$this->db->field_exists('ikon_kecil', 'setting_modul'))
		{
			$fields = array(
				'ikon_kecil' => array(
					'type' => 'VARCHAR',
					'constraint' => 50
				)
			);
			$this->dbforge->add_column('setting_modul', $fields);
			$list_modul = array(
				"1" => "fa fa-home fa-lg",         // SID Home
				"2" => "fa fa-group fa-lg",        // Penduduk
				"3" => "fa fa-bar-chart fa-lg",    // Statistik
				"4" => "fa fa-print fa-lg",        // Cetak Surat
				"5" => "fa fa-dashboard fa-lg",    // Analisis
				"6" => "fa fa-folder-open fa-lg",  // Bantuan
				"7" => "fa fa-road fa-lg",         // Persil
				"8" => "fa fa-sitemap fa-lg",      // Plan
				"9" => "fa fa-map fa-lg",          // Peta
				"10" => "fa fa-envelope-o fa-lg",  // SMS
				"11" => "fa fa-user-plus fa-lg",   // Pengguna
				"12" => "fa fa-database fa-lg",    // Database
				"13" => "fa fa-cloud fa-lg",       // Admin Web
				"14" => "fa fa-comments fa-lg");   // Laporan
			foreach ($list_modul as $key => $value)
			{
				$this->db->where('id',$key)->update('setting_modul', array('ikon_kecil' => $value));
			}
		}
		// Tambah kolom id_pend di tabel tweb_penduduk_mandiri
		if (!$this->db->field_exists('id_pend', 'tweb_penduduk_mandiri'))
		{
			$fields = array(
				'id_pend' => array(
					'type' => 'int',
					'constraint' => 9,
					'null' => FALSE,
					'first' => TRUE
				)
			);
			$this->dbforge->add_column('tweb_penduduk_mandiri', $fields);
		}
		// Isi kolom id_pend
		$mandiri = $this->db->select('nik')->get('tweb_penduduk_mandiri')->result_array();
		foreach ($mandiri as $individu) {
			$id_pend = $this->db->select('id')->where('nik', $individu['nik'])->get('tweb_penduduk')->row()->id;
			if (empty($id_pend))
				$this->db->where('nik',$individu['nik'])->delete('tweb_penduduk_mandiri');
			else
				$this->db->where('nik',$individu['nik'])->update('tweb_penduduk_mandiri',array('id_pend' => $id_pend));
		}
		// Buat id_pend menjadi primary key
		$sql = "ALTER TABLE tweb_penduduk_mandiri
							DROP PRIMARY KEY,
							ADD PRIMARY KEY (id_pend)";
		$this->db->query($sql);
		// Tambah kolom kategori di tabel dokumen
		if (!$this->db->field_exists('kategori', 'dokumen'))
		{
			$fields = array(
				'kategori' => array(
					'type' => 'tinyint',
					'constraint' => 3,
					'default' => 1
				)
			);
			$this->dbforge->add_column('dokumen', $fields);
		}
		// Tambah kolom attribute dokumen
		if (!$this->db->field_exists('attr', 'dokumen'))
		{
			$fields = array(
				'attr' => array(
					'type' => 'text'
				)
			);
			$this->dbforge->add_column('dokumen', $fields);
		}
	}

	private function migrasi_23_ke_24()
	{
		// Tambah surat keterangan beda identitas KIS
		$data = array(
			'nama'=>'Keterangan Beda Identitas KIS',
			'url_surat'=>'surat_ket_beda_identitas_kis',
			'kode_surat'=>'S-38',
			'jenis'=>1);
		$sql = $this->db->insert_string('tweb_surat_format', $data);
		$sql .= " ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis)";
		$this->db->query($sql);
		// Tambah setting sebutan kepala dusun
		$setting = $this->db->where('key','sebutan_singkatan_kadus')->get('setting_aplikasi')->row()->id;
		if (!$setting)
		{
			$this->db->insert('setting_aplikasi',array('key'=>'sebutan_singkatan_kadus','value'=>'kawil','keterangan'=>'Sebutan singkatan jabatan kepala dusun'));
		}
	}

	private function migrasi_22_ke_23()
	{
		// Tambah widget menu_left untuk menampilkan menu kategori
		$widget = $this->db->select('id')->where('isi','menu_kategori.php')->get('widget')->row();
		if (!$widget->id)
		{
			$menu_kategori = array('judul'=>'Menu Kategori','isi'=>'menu_kategori.php','enabled'=>1,'urut'=>1,'jenis_widget'=>1);
			$this->db->insert('widget',$menu_kategori);
		}
		// Tambah tabel surat_masuk
		if (!$this->db->table_exists('surat_masuk') )
		{
			$query = "
				CREATE TABLE `surat_masuk` (
					`id` int NOT NULL AUTO_INCREMENT,
					`nomor_urut` smallint(5),
					`tanggal_penerimaan` date NOT NULL,
					`nomor_surat` varchar(20),
					`kode_surat` varchar(10),
					`tanggal_surat` date NOT NULL,
					`pengirim` varchar(100),
					`isi_singkat` varchar(200),
					`disposisi_kepada` varchar(50),
					`isi_disposisi` varchar(200),
					`berkas_scan` varchar(100),
					PRIMARY KEY  (`id`)
				);
			";
			$this->db->query($query);
		}
		// Artikel bisa di-comment atau tidak
		if (!$this->db->field_exists('boleh_komentar', 'artikel'))
		{
			$fields = array(
				'boleh_komentar' => array(
					'type' => 'tinyint',
					'constraint' => 1,
					'default' => 1
				)
			);
			$this->dbforge->add_column('artikel', $fields);
		}
	}

	private function migrasi_21_ke_22()
	{
		// Tambah lampiran untuk Surat Keterangan Kelahiran
		$this->db->where('url_surat','surat_ket_kelahiran')->update('tweb_surat_format',array('lampiran'=>'f-kelahiran.php'));
		// Tambah setting sumber gambar slider
		$pilihan_sumber = $this->db->where('key','sumber_gambar_slider')->get('setting_aplikasi')->row()->id;
		if (!$pilihan_sumber)
		{
			$this->db->insert('setting_aplikasi',array('key'=>'sumber_gambar_slider','value'=>1,'keterangan'=>'Sumber gambar slider besar'));
		}
		// Tambah gambar kartu peserta program bantuan
		if (!$this->db->field_exists('kartu_peserta', 'program_peserta'))
		{
			$fields = array(
				'kartu_peserta' => array(
					'type' => 'VARCHAR',
					'constraint' => 100
				)
			);
			$this->dbforge->add_column('program_peserta', $fields);
		}
	}

	private function migrasi_20_ke_21()
	{
		if (!$this->db->table_exists('widget') )
		{
			$query = "
				CREATE TABLE `widget` (
					`id` int NOT NULL AUTO_INCREMENT,
					`isi` text,
					`enabled` int(2),
					`judul` varchar(100),
					`jenis_widget` tinyint(2) NOT NULL DEFAULT 3,
					`urut` int(5),
					PRIMARY KEY  (`id`)
				);
			";
			$this->db->query($query);
			// Pindahkan data widget dari tabel artikel ke tabel widget
			$widgets = $this->db->select('isi, enabled, judul, jenis_widget, urut')->where('id_kategori', 1003)->get('artikel')->result_array();
			foreach ($widgets as $widget)
			{
				$this->db->insert('widget', $widget);
			}
			// Hapus kolom widget dari tabel artikel
			$kolom_untuk_dihapus = array("urut", "jenis_widget");
			foreach ($kolom_untuk_dihapus as $kolom){
				$this->dbforge->drop_column('artikel', $kolom);
			}
		}
		// Hapus setiap kali migrasi, karena ternyata masih ada di database contoh s/d v2.4
		// TODO: pindahkan ini jika nanti ada kategori dengan nilai 1003.
		$this->db->where('id_kategori',1003)->delete('artikel');
		// Tambah tautan ke form administrasi widget
		if (!$this->db->field_exists('form_admin', 'widget'))
		{
			$fields = array(
				'form_admin' => array(
					'type' => 'VARCHAR',
					'constraint' => 100
				)
			);
			$this->dbforge->add_column('widget', $fields);
			$this->db->where('isi','layanan_mandiri.php')->update('widget',array('form_admin'=>'mandiri'));
			$this->db->where('isi','aparatur_desa.php')->update('widget',array('form_admin'=>'pengurus'));
			$this->db->where('isi','agenda.php')->update('widget',array('form_admin'=>'web/index/1000'));
			$this->db->where('isi','galeri.php')->update('widget',array('form_admin'=>'gallery'));
			$this->db->where('isi','komentar.php')->update('widget',array('form_admin'=>'komentar'));
			$this->db->where('isi','media_sosial.php')->update('widget',array('form_admin'=>'sosmed'));
			$this->db->where('isi','peta_lokasi_kantor.php')->update('widget',array('form_admin'=>'hom_desa'));
		}
		// Tambah kolom setting widget
		if (!$this->db->field_exists('setting', 'widget'))
		{
			$fields = array(
				'setting' => array(
					'type' => 'text'
				)
			);
			$this->dbforge->add_column('widget', $fields);
		}
		// Ubah nama widget menjadi sinergi_program
		$this->db->select('id')->where('isi','sinergitas_program.php')->update('widget', array('isi'=>'sinergi_program.php', 'judul'=>'Sinergi Program','form_admin'=>'web_widget/admin/sinergi_program'));
		// Tambah widget sinergi_program
		$widget = $this->db->select('id')->where('isi','sinergi_program.php')->get('widget')->row();
		if (!$widget->id)
		{
			$widget_baru = array('judul'=>'Sinergi Program','isi'=>'sinergi_program.php','enabled'=>1,'urut'=>1,'jenis_widget'=>1,'form_admin'=>'web_widget/admin/sinergi_program');
			$this->db->insert('widget',$widget_baru);
		}
	}

	private function migrasi_117_ke_20()
	{
		if (!$this->db->table_exists('setting_aplikasi') )
		{
			$query = "
				CREATE TABLE `setting_aplikasi` (
					`id` int NOT NULL AUTO_INCREMENT,
					`key` varchar(50),
					`value` varchar(200),
					`keterangan` varchar(200),
					`jenis` varchar(30),
					`kategori` varchar(30),
					PRIMARY KEY  (`id`)
				);
			";
			$this->db->query($query);

			$this->reset_setting_aplikasi();
		}
		// Update untuk tambahan offline mode 2, sesudah masuk pra-rilis (ada yang sudah migrasi)
		$this->db->where('id',12)->update('setting_aplikasi',array('value'=>'0','jenis'=>''));
		// Update media_sosial
		$this->db->where('id',3)->update('media_sosial',array('nama'=>'Google Plus'));
		$this->db->where('id',4)->update('media_sosial',array('nama'=>'YouTube'));
		// Tambah widget aparatur_desa
		$widget = $this->db->select('id')->where(array('isi'=>'aparatur_desa.php', 'id_kategori'=>1003))->get('artikel')->row();
		if (!$widget->id)
		{
			$aparatur_desa = array('judul'=>'Aparatur Desa','isi'=>'aparatur_desa.php','enabled'=>1,'id_kategori'=>1003,'urut'=>1,'jenis_widget'=>1);
			$this->db->insert('artikel',$aparatur_desa);
		}
		// Tambah foto aparatur desa
		if (!$this->db->field_exists('foto', 'tweb_desa_pamong'))
		{
			$fields = array(
				'foto' => array(
					'type' => 'VARCHAR',
					'constraint' => 100
				)
			);
			$this->dbforge->add_column('tweb_desa_pamong', $fields);
		}
	}

	private function migrasi_116_ke_117()
	{
		// Tambah kolom log_penduduk
		if (!$this->db->field_exists('no_kk', 'log_penduduk'))
		{
			$query = "ALTER TABLE log_penduduk ADD no_kk decimal(16,0)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('nama_kk', 'log_penduduk'))
		{
			$query = "ALTER TABLE log_penduduk ADD nama_kk varchar(100)";
			$this->db->query($query);
		}
		// Hapus surat_ubah_sesuaikan
		$this->db->where('url_surat', 'surat_ubah_sesuaikan')->delete('tweb_surat_format');
		// Tambah kolom log_surat untuk surat non-warga
		if (!$this->db->field_exists('nik_non_warga', 'log_surat'))
		{
			$query = "ALTER TABLE log_surat ADD nik_non_warga decimal(16,0)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('nama_non_warga', 'log_surat'))
		{
			$query = "ALTER TABLE log_surat ADD nama_non_warga varchar(100)";
			$this->db->query($query);
		}
		$query = "ALTER TABLE log_surat MODIFY id_pend int(11) DEFAULT NULL";
		$this->db->query($query);
		// Tambah contoh surat non-warga
		$query = "
			INSERT INTO tweb_surat_format(nama, url_surat, kode_surat, jenis) VALUES
			('Domisili Usaha Non-Warga', 'surat_domisili_usaha_non_warga', 'S-37', 1)
			ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis);
		";
		$this->db->query($query);
	}

	private function migrasi_115_ke_116()
	{
		// Ubah surat N-1 menjadi surat gabungan N-1 s/d N-7
		$this->db->where('url_surat','surat_ket_nikah')->update('tweb_surat_format',array('nama'=>'Keterangan Untuk Nikah (N-1 s/d N-7)'));
		// Hapus surat N-2 s/d N-7 yang sudah digabungkan ke surat_ket_nikah
		$this->db->where('url_surat','surat_ket_asalusul')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_persetujuan_mempelai')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_ket_orangtua')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_izin_orangtua')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_ket_kematian_suami_istri')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_kehendak_nikah')->delete('tweb_surat_format');
		$this->db->where('url_surat','surat_ket_wali')->delete('tweb_surat_format');
		// Tambah kolom untuk penandatangan surat
		if (!$this->db->field_exists('pamong_ttd', 'tweb_desa_pamong')) {
			$query = "ALTER TABLE tweb_desa_pamong ADD pamong_ttd tinyint(1)";
			$this->db->query($query);
		}
		// Hapus surat_pindah_antar_kab_prov
		$this->db->where('url_surat','surat_pindah_antar_kab_prov')->delete('tweb_surat_format');
	}

	private function migrasi_114_ke_115()
	{
		// Tambah kolom untuk peserta program
		if (!$this->db->field_exists('kartu_nik', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD kartu_nik decimal(16,0)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kartu_nama', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD kartu_nama varchar(100)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kartu_tempat_lahir', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD kartu_tempat_lahir varchar(100)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kartu_tanggal_lahir', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD kartu_tanggal_lahir date";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kartu_alamat', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD kartu_alamat varchar(200)";
			$this->db->query($query);
		}
	}

	private function migrasi_113_ke_114()
	{
		// Tambah kolom untuk slider
		if (!$this->db->field_exists('slider', 'gambar_gallery'))
		{
			$query = "ALTER TABLE gambar_gallery ADD slider tinyint(1)";
			$this->db->query($query);
		}
	}

	private function migrasi_112_ke_113()
	{
		// Tambah data desa
		if (!$this->db->field_exists('nip_kepala_desa', 'config'))
		{
			$query = "ALTER TABLE config ADD nip_kepala_desa decimal(18,0)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('email_desa', 'config'))
		{
			$query = "ALTER TABLE config ADD email_desa varchar(50)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('telepon', 'config'))
		{
			$query = "ALTER TABLE config ADD telepon varchar(50)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('website', 'config'))
		{
			$query = "ALTER TABLE config ADD website varchar(100)";
			$this->db->query($query);
		}
		// Gabung F-1.15 dan F-1.01 menjadi satu lampiran surat_permohonan_kartu_keluarga
		$this->db->where('url_surat','surat_permohonan_kartu_keluarga')->update('tweb_surat_format',array('lampiran'=>'f-1.15.php,f-1.01.php'));
	}

	// Berdasarkan analisa database yang dikirim oleh AdJie Reverb Impulse
	private function migrasi_cri_lama()
	{
		if (!$this->db->field_exists('enabled', 'kategori'))
		{
			$query = "ALTER TABLE kategori ADD enabled tinyint(4) DEFAULT 1";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('parrent', 'kategori'))
		{
			$query = "ALTER TABLE kategori ADD parrent tinyint(4) DEFAULT 0";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kode_surat', 'tweb_surat_format'))
		{
			$query = "ALTER TABLE tweb_surat_format ADD kode_surat varchar(10)";
			$this->db->query($query);
		}
	}

	private function migrasi_03_ke_04()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `tweb_penduduk_mandiri` (
				`nik` decimal(16,0) NOT NULL,
				`pin` char(32) NOT NULL,
				`last_login` datetime,
				`tanggal_buat` date NOT NULL,
				PRIMARY KEY  (`nik`)
			);
		";
		$this->db->query($query);

		$query = "
			CREATE TABLE IF NOT EXISTS `program` (
				`id` int NOT NULL AUTO_INCREMENT,
				`nama` varchar(100) NOT NULL,
				`sasaran` tinyint,
				`ndesc` varchar(200),
				`sdate` date NOT NULL,
				`edate` date NOT NULL,
				`userid` mediumint NOT NULL,
				`status` int(10),
				PRIMARY KEY  (`id`)
			);
		";
		$this->db->query($query);

		$query = "
			CREATE TABLE IF NOT EXISTS `program_peserta` (
				`id` int NOT NULL AUTO_INCREMENT,
				`peserta` decimal(16,0) NOT NULL,
				`program_id` int NOT NULL,
				`sasaran` tinyint,
				PRIMARY KEY  (`id`)
			);
		";
		$this->db->query($query);

		$query = "
			CREATE TABLE IF NOT EXISTS `data_persil` (
				`id` int NOT NULL AUTO_INCREMENT,
				`nik` decimal(16,0) NOT NULL,
				`nama` varchar(100) NOT NULL,
				`persil_jenis_id` int NOT NULL,
				`id_clusterdesa` int NOT NULL,
				`luas` int,
				`no_sppt_pbb` int,
				`kelas` varchar(50),
				`persil_peruntukan_id` int NOT NULL,
				`alamat_ext` varchar(100),
				`userID` mediumint,
				PRIMARY KEY  (`id`)
			);
		";
		$this->db->query($query);

		$query = "
			CREATE TABLE IF NOT EXISTS `data_persil_peruntukan` (
				`id` int NOT NULL AUTO_INCREMENT,
				`nama` varchar(100) NOT NULL,
				`ndesc` varchar(200),
				PRIMARY KEY  (`id`)
			);
		";
		$this->db->query($query);

		$query = "
			CREATE TABLE IF NOT EXISTS `data_persil_jenis` (
				`id` int NOT NULL AUTO_INCREMENT,
				`nama` varchar(100) NOT NULL,
				`ndesc` varchar(200),
				PRIMARY KEY  (`id`)
			);
		";
		$this->db->query($query);
	}

	private function migrasi_08_ke_081()
	{
		if (!$this->db->field_exists('nama_surat', 'log_surat'))
		{
			$query = "ALTER TABLE `log_surat` ADD `nama_surat` varchar(100)";
			$this->db->query($query);
		}
	}

	private function migrasi_082_ke_09()
	{
		if (!$this->db->field_exists('catatan', 'log_penduduk'))
		{
			$query = "ALTER TABLE `log_penduduk` ADD `catatan` text";
			$this->db->query($query);
		}
	}

	private function migrasi_092_ke_010()
	{
		// CREATE UNIQUE INDEX migrasi_0_10_url_surat ON tweb_surat_format (url_surat);

		// Hapus surat duplikat
		$kriteria = array('id' => 19, 'url_surat' => 'surat_ket_kehilangan');
		$this->db->where($kriteria);
		$this->db->delete('tweb_surat_format');

		$query = "
			INSERT INTO `tweb_surat_format` (`id`, `nama`, `url_surat`, `kode_surat`) VALUES
			(1, 'Keterangan Pengantar', 'surat_ket_pengantar', 'S-01'),
			(2, 'Keterangan Penduduk', 'surat_ket_penduduk', 'S-02'),
			(3, 'Biodata Penduduk', 'surat_bio_penduduk', 'S-03'),
			(5, 'Keterangan Pindah Penduduk', 'surat_ket_pindah_penduduk', 'S-04'),
			(6, 'Keterangan Jual Beli', 'surat_ket_jual_beli', 'S-05'),
			(7, 'Pengantar Pindah Antar Kabupaten/ Provinsi', 'surat_pindah_antar_kab_prov', 'S-06'),
			(8, 'Pengantar Surat Keterangan Catatan Kepolisian', 'surat_ket_catatan_kriminal', 'S-07'),
			(9, 'Keterangan KTP dalam Proses', 'surat_ket_ktp_dalam_proses', 'S-08'),
			(10, 'Keterangan Beda Identitas', 'surat_ket_beda_nama', 'S-09'),
			(11, 'Keterangan Bepergian / Jalan', 'surat_jalan', 'S-10'),
			(12, 'Keterangan Kurang Mampu', 'surat_ket_kurang_mampu', 'S-11'),
			(13, 'Pengantar Izin Keramaian', 'surat_izin_keramaian', 'S-12'),
			(14, 'Pengantar Laporan Kehilangan', 'surat_ket_kehilangan', 'S-13'),
			(15, 'Keterangan Usaha', 'surat_ket_usaha', 'S-14'),
			(16, 'Keterangan JAMKESOS', 'surat_ket_jamkesos', 'S-15'),
			(17, 'Keterangan Domisili Usaha', 'surat_ket_domisili_usaha', 'S-16'),
			(18, 'Keterangan Kelahiran', 'surat_ket_kelahiran', 'S-17'),
			(20, 'Permohonan Akta Lahir', 'surat_permohonan_akta', 'S-18'),
			(21, 'Pernyataan Belum Memiliki Akta Lahir', 'surat_pernyataan_akta', 'S-19'),
			(22, 'Permohonan Duplikat Kelahiran', 'surat_permohonan_duplikat_kelahiran', 'S-20'),
			(24, 'Keterangan Kematian', 'surat_ket_kematian', 'S-21'),
			(25, 'Keterangan Lahir Mati', 'surat_ket_lahir_mati', 'S-22'),
			(26, 'Keterangan Untuk Nikah (N-1)', 'surat_ket_nikah', 'S-23'),
			(27, 'Keterangan Asal Usul (N-2)', 'surat_ket_asalusul', 'S-24'),
			(28, 'Persetujuan Mempelai (N-3)', 'surat_persetujuan_mempelai', 'S-25'),
			(29, 'Keterangan Tentang Orang Tua (N-4)', 'surat_ket_orangtua', 'S-26'),
			(30, 'Keterangan Izin Orang Tua(N-5)', 'surat_izin_orangtua', 'S-27'),
			(31, 'Keterangan Kematian Suami/Istri(N-6)', 'surat_ket_kematian_suami_istri', 'S-28'),
			(32, 'Pemberitahuan Kehendak Nikah (N-7)', 'surat_kehendak_nikah', 'S-29'),
			(33, 'Keterangan Pergi Kawin', 'surat_ket_pergi_kawin', 'S-30'),
			(34, 'Keterangan Wali', 'surat_ket_wali', 'S-31'),
			(35, 'Keterangan Wali Hakim', 'surat_ket_wali_hakim', 'S-32'),
			(36, 'Permohonan Duplikat Surat Nikah', 'surat_permohonan_duplikat_surat_nikah', 'S-33'),
			(37, 'Permohonan Cerai', 'surat_permohonan_cerai', 'S-34'),
			(38, 'Keterangan Pengantar Rujuk/Cerai', 'surat_ket_rujuk_cerai', 'S-35')
			ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat);
		";
		$this->db->query($query);
		// surat_ubah_sesuaikan perlu ditangani berbeda, karena ada pengguna di mana
		// url surat_ubah_sesuaikan memiliki id yang bukan 39, sedangkan id 39 juga dipakai untuk surat lain
		$this->db->where('url_surat', 'surat_ubah_sesuaikan');
		$query = $this->db->get('tweb_surat_format');
		// Tambahkan surat_ubah_sesuaikan apabila belum ada
		if ($query->num_rows() == 0)
		{
			$data = array(
				'nama' => 'Ubah Sesuaikan',
				'url_surat' => 'surat_ubah_sesuaikan',
				'kode_surat' => 'S-36'
			);
			$this->db->insert('tweb_surat_format', $data);
		}

		// DROP INDEX migrasi_0_10_url_surat ON tweb_surat_format;

		/* Jangan buat index unik kode_surat, karena kolom ini digunakan
			 untuk merekam klasifikasi surat yang tidak unik. */
		// $db = $this->db->database;
		// $query = "
		//   SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS
		//   WHERE table_schema=? AND table_name='tweb_surat_format' AND index_name='kode_surat';
		// ";
		// $hasil = $this->db->query($query, $db);
		// $data = $hasil->row_array();
		// if ($data['IndexIsThere'] == 0) {
		//   $query = "
		//     CREATE UNIQUE INDEX kode_surat ON tweb_surat_format (kode_surat);
		//   ";
		//   $this->db->query($query);
		// }

		if (!$this->db->field_exists('tgl_cetak_kk', 'tweb_keluarga'))
		{
			$query = "ALTER TABLE tweb_keluarga ADD tgl_cetak_kk datetime";
			$this->db->query($query);
		}
		$query = "ALTER TABLE tweb_penduduk_mandiri MODIFY tanggal_buat datetime";
		$this->db->query($query);
	}

	private function migrasi_010_ke_10()
	{
		$query = "
			INSERT INTO tweb_penduduk_pekerjaan(id, nama) VALUES (89, 'LAINNYA')
			ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				nama = VALUES(nama);
		";
		$this->db->query($query);
	}

	private function migrasi_10_ke_11()
	{
		if (!$this->db->field_exists('kk_lk', 'log_bulanan'))
		{
			$query = "ALTER TABLE log_bulanan ADD kk_lk int(11)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('kk_pr', 'log_bulanan'))
		{
			$query = "ALTER TABLE log_bulanan ADD kk_pr int(11)";
			$this->db->query($query);
		}

		if (!$this->db->field_exists('urut', 'artikel'))
		{
			$query = "ALTER TABLE artikel ADD urut int(5)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('jenis_widget', 'artikel'))
		{
			$query = "ALTER TABLE artikel ADD jenis_widget tinyint(2) NOT NULL DEFAULT 3";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('log_keluarga') )
		{
			$query = "
				CREATE TABLE `log_keluarga` (
					`id` int(10) NOT NULL AUTO_INCREMENT,
					`id_kk` int(11) NOT NULL,
					`kk_sex` tinyint(2) NOT NULL,
					`id_peristiwa` int(4) NOT NULL,
					`tgl_peristiwa` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					UNIQUE KEY `id_kk` (`id_kk`,`id_peristiwa`,`tgl_peristiwa`)
				) ENGINE=".$this->engine." AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
			";
			$this->db->query($query);
		}

		$query = "
			DROP VIEW IF EXISTS data_surat;
		";
		$this->db->query($query);

		$query = "
			DROP TABLE IF EXISTS data_surat;
		";
		$this->db->query($query);

		$query = "
			CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `data_surat` AS select `u`.`id` AS `id`,`u`.`nama` AS `nama`,`x`.`nama` AS `sex`,`u`.`tempatlahir` AS `tempatlahir`,`u`.`tanggallahir` AS `tanggallahir`,(select (date_format(from_days((to_days(now()) - to_days(`tweb_penduduk`.`tanggallahir`))),'%Y') + 0) from `tweb_penduduk` where (`tweb_penduduk`.`id` = `u`.`id`)) AS `umur`,`w`.`nama` AS `status_kawin`,`f`.`nama` AS `warganegara`,`a`.`nama` AS `agama`,`d`.`nama` AS `pendidikan`,`j`.`nama` AS `pekerjaan`,`u`.`nik` AS `nik`,`c`.`rt` AS `rt`,`c`.`rw` AS `rw`,`c`.`dusun` AS `dusun`,`k`.`no_kk` AS `no_kk`,(select `tweb_penduduk`.`nama` from `tweb_penduduk` where (`tweb_penduduk`.`id` = `k`.`nik_kepala`)) AS `kepala_kk` from ((((((((`tweb_penduduk` `u` left join `tweb_penduduk_sex` `x` on((`u`.`sex` = `x`.`id`))) left join `tweb_penduduk_kawin` `w` on((`u`.`status_kawin` = `w`.`id`))) left join `tweb_penduduk_agama` `a` on((`u`.`agama_id` = `a`.`id`))) left join `tweb_penduduk_pendidikan_kk` `d` on((`u`.`pendidikan_kk_id` = `d`.`id`))) left join `tweb_penduduk_pekerjaan` `j` on((`u`.`pekerjaan_id` = `j`.`id`))) left join `tweb_wil_clusterdesa` `c` on((`u`.`id_cluster` = `c`.`id`))) left join `tweb_keluarga` `k` on((`u`.`id_kk` = `k`.`id`))) left join `tweb_penduduk_warganegara` `f` on((`u`.`warganegara_id` = `f`.`id`)));
		";
		$this->db->query($query);

		$system_widgets = array(
			'Layanan Mandiri'      => 'layanan_mandiri.php',
			'Agenda'               => 'agenda.php',
			'Galeri'               => 'galeri.php',
			'Statistik'            => 'statistik.php',
			'Komentar'             => 'komentar.php',
			'Media Sosial'         => 'media_sosial.php',
			'Peta Lokasi Kantor'   => 'peta_lokasi_kantor.php',
			'Statistik Pengunjung' => 'statistik_pengunjung.php',
			'Arsip Artikel'        => 'arsip_artikel.php'
		);

		foreach ($system_widgets as $key => $value)
		{
			$this->db->select('id');
			$this->db->where(array('isi' => $value, 'id_kategori' => 1003));
			$q = $this->db->get('artikel');
			$widget = $q->row_array();
			if (!$widget['id'])
			{
				$query = "
					INSERT INTO artikel (judul,isi,enabled,id_kategori,urut,jenis_widget)
					VALUES ('$key','$value',1,1003,1,1);";
				$this->db->query($query);
			}
		}
	}

	private function migrasi_111_ke_12()
	{
		if (!$this->db->field_exists('alamat', 'tweb_keluarga'))
		{
			$query = "ALTER TABLE tweb_keluarga ADD alamat varchar(200)";
			$this->db->query($query);
		}
	}

	private function migrasi_124_ke_13()
	{
		if (!$this->db->field_exists('urut', 'menu'))
		{
			$query = "ALTER TABLE menu ADD urut int(5)";
			$this->db->query($query);
		}
	}

	private function migrasi_13_ke_14()
	{
		$query = "
			INSERT INTO user_grup (id, nama) VALUES (4, 'Kontributor')
			ON DUPLICATE KEY UPDATE
				id = VALUES(id),
				nama = VALUES(nama);
		";
		$this->db->query($query);

		// Buat tanggalperkawinan dan tanggalperceraian boleh NULL
		$query = "ALTER TABLE tweb_penduduk CHANGE tanggalperkawinan tanggalperkawinan DATE NULL DEFAULT NULL;";
		$this->db->query($query);
		$query = "ALTER TABLE tweb_penduduk CHANGE tanggalperceraian tanggalperceraian DATE NULL DEFAULT NULL;";
		$this->db->query($query);

		 // Ubah tanggal menjadi NULL apabila 0000-00-00
		$query = "UPDATE tweb_penduduk SET tanggalperkawinan=NULL WHERE tanggalperkawinan='0000-00-00' OR tanggalperkawinan='00-00-0000';";
		$this->db->query($query);
		$query = "UPDATE tweb_penduduk SET tanggalperceraian=NULL WHERE tanggalperceraian='0000-00-00' OR tanggalperceraian='00-00-0000';";
		$this->db->query($query);
	}

	private function migrasi_14_ke_15()
	{
		// Tambah kolom di tabel tweb_penduduk
		if (!$this->db->field_exists('cara_kb_id', 'tweb_penduduk'))
		{
			$query = "ALTER TABLE tweb_penduduk ADD cara_kb_id tinyint(2) NULL DEFAULT NULL;";
			$this->db->query($query);
		}

		 // Tambah tabel cara_kb
		$query = "DROP TABLE IF EXISTS tweb_cara_kb;";
		$this->db->query($query);

		$query = "
			CREATE TABLE tweb_cara_kb (
				id tinyint(5) NOT NULL AUTO_INCREMENT,
				nama varchar(50) NOT NULL,
				sex tinyint(2),
				PRIMARY KEY (id)
			) ENGINE=".$this->engine." AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
		";
		$this->db->query($query);

		$query = "
			INSERT INTO tweb_cara_kb (id, nama, sex) VALUES
			(1, 'Pil', 2),
			(2, 'IUD', 2),
			(3, 'Suntik', 2),
			(4, 'Kondom', 1),
			(5, 'Susuk KB', 2),
			(6, 'Sterilisasi Wanita', 2),
			(7, 'Sterilisasi Pria', 1),
			(99, 'Lainnya', 3);
		";
		$this->db->query($query);

		 // Ubah tanggallahir supaya tidak tampil apabila kosong
		$query = "ALTER TABLE tweb_penduduk CHANGE tanggallahir tanggallahir DATE NULL DEFAULT NULL;";
		$this->db->query($query);
		$query = "
			UPDATE tweb_penduduk SET tanggallahir=NULL
			WHERE tanggallahir='0000-00-00' OR tanggallahir='00-00-0000';
		";
		$this->db->query($query);
	}

	private function migrasi_15_ke_16()
	{
		// Buat kk_sex boleh NULL
		$query = "ALTER TABLE log_keluarga CHANGE kk_sex kk_sex tinyint(2) NULL DEFAULT NULL;";
		$this->db->query($query);

		// ==== Gabung program bantuan keluarga statik ke dalam modul Program Bantuan

		$program_keluarga = array(
			"Raskin" => "raskin",
			"BLSM"   => "id_blt",
			"PKH"    => "id_pkh",
			"Bedah Rumah" => "id_bedah_rumah"
		);
		foreach ($program_keluarga as $key => $value)
		{
			// cari keluarga anggota program
			if (!$this->db->field_exists($value, 'tweb_keluarga')) continue;

			$this->db->select("no_kk");
			$this->db->where("$value",1);
			$q = $this->db->get("tweb_keluarga");
			if ( $q->num_rows() > 0 )
			{
				// buat program
				$data = array(
					'sasaran' => 2,
					'nama' => $key,
					'ndesc' => '',
					'userid' => 0,
					'sdate' => date("Y-m-d",strtotime("-1 year")),
					'edate' => date("Y-m-d",strtotime("+1 year"))
				);
				$this->db->insert('program', $data);
				$id_program = $this->db->insert_id();
				// untuk setiap keluarga anggota program buat program_peserta
				$data = $q->result_array();
				foreach ($data as $peserta_keluarga)
				{
					$peserta = array(
						'peserta' => $peserta_keluarga['no_kk'],
						'program_id' => $id_program,
						'sasaran' => 2
					);
					$this->db->insert('program_peserta', $peserta);
				}
			}
			// Hapus kolom program di tweb_keluarga
			$sql = "ALTER TABLE tweb_keluarga DROP COLUMN $value";
			$this->db->query($sql);
		}
		// ==== Gabung program bantuan penduduk statik ke dalam modul Program Bantuan

		$program_penduduk = array(
			"JAMKESMAS" => "jamkesmas"
		);
		foreach ($program_penduduk as $key => $value)
		{
			// cari penduduk anggota program
			if (!$this->db->field_exists($value, 'tweb_penduduk')) continue;

			$this->db->select("nik");
			$this->db->where("$value",1);
			$q = $this->db->get("tweb_penduduk");
			if ( $q->num_rows() > 0 )
			{
				// buat program
				$data = array(
					'sasaran' => 1,
					'nama' => $key,
					'ndesc' => '',
					'userid' => 0,
					'sdate' => date("Y-m-d",strtotime("-1 year")),
					'edate' => date("Y-m-d",strtotime("+1 year"))
				);
				$this->db->insert('program', $data);
				$id_program = $this->db->insert_id();
				// untuk setiap penduduk anggota program buat program_peserta
				$data = $q->result_array();
				foreach ($data as $peserta_penduduk)
				{
					$peserta = array(
						'peserta' => $peserta_penduduk['nik'],
						'program_id' => $id_program,
						'sasaran' => 2
					);
					$this->db->insert('program_peserta', $peserta);
				}
			}
			// Hapus kolom program di tweb_penduduk
			$sql = "ALTER TABLE tweb_penduduk DROP COLUMN $value";
			$this->db->query($sql);
		}
	}

	private function migrasi_16_ke_17()
	{
		// Tambahkan id_cluster ke tabel keluarga
		if (!$this->db->field_exists('id_cluster', 'tweb_keluarga'))
		{
			$query = "ALTER TABLE tweb_keluarga ADD id_cluster int(11);";
			$this->db->query($query);

			// Untuk setiap keluarga
			$query = $this->db->get('tweb_keluarga');
			$data = $query->result_array();
			foreach ($data as $keluarga)
			{
				// Ambil id_cluster kepala keluarga
				$this->db->select('id_cluster');
				$this->db->where('id', $keluarga['nik_kepala']);
				$query = $this->db->get('tweb_penduduk');
				$kepala_kk = $query->row_array();
				// Tulis id_cluster kepala keluarga ke keluarga
				if (isset($kepala_kk['id_cluster'])) {
					$this->db->where('id', $keluarga['id']);
					$this->db->update('tweb_keluarga', array('id_cluster' => $kepala_kk['id_cluster']));
				}
			}
		}
	}

	private function migrasi_17_ke_18()
	{
		// Tambah lampiran surat dgn template html2pdf
		if (!$this->db->field_exists('lampiran', 'log_surat'))
		{
			$query = "ALTER TABLE `log_surat` ADD `lampiran` varchar(100)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('lampiran', 'tweb_surat_format'))
		{
			$query = "ALTER TABLE `tweb_surat_format` ADD `lampiran` varchar(100)";
			$this->db->query($query);
		}
		$query = "
			INSERT INTO `tweb_surat_format` (`id`, `url_surat`, `lampiran`) VALUES
			(5, 'surat_ket_pindah_penduduk', 'f-1.08.php')
			ON DUPLICATE KEY UPDATE
				url_surat = VALUES(url_surat),
				lampiran = VALUES(lampiran);
		";
		$this->db->query($query);
	}

	private function migrasi_18_ke_19()
	{
		// Hapus index unik untuk kode_surat kalau sempat dibuat sebelumnya
		$db = $this->db->database;
		$query = "
			SELECT COUNT(1) IndexIsThere FROM INFORMATION_SCHEMA.STATISTICS
			WHERE table_schema=? AND table_name='tweb_surat_format' AND index_name='kode_surat';
		";
		$hasil = $this->db->query($query, $db);
		$data = $hasil->row_array();
		if ($data['IndexIsThere'] > 0)
		{
			$query = "
				DROP INDEX kode_surat ON tweb_surat_format;
			";
			$this->db->query($query);
		}

		// Hapus tabel yang tidak terpakai lagi
		$query = "DROP TABLE IF EXISTS ref_bedah_rumah, ref_blt, ref_jamkesmas, ref_pkh, ref_raskin, tweb_alamat_sekarang";
		$this->db->query($query);
	}

	private function migrasi_19_ke_110()
	{
		// Tambah nomor id_kartu untuk peserta program bantuan
		if (!$this->db->field_exists('no_id_kartu', 'program_peserta'))
		{
			$query = "ALTER TABLE program_peserta ADD no_id_kartu varchar(30)";
			$this->db->query($query);
		}
	}

	private function migrasi_110_ke_111()
	{
		// Buat folder desa/upload/pengesahan apabila belum ada
		if (!file_exists(LOKASI_PENGESAHAN))
		{
			mkdir(LOKASI_PENGESAHAN, 0755);
		}
		// Tambah akti/non-aktifkan dan pilihan favorit format surat
		if (!$this->db->field_exists('kunci', 'tweb_surat_format'))
		{
			$query = "ALTER TABLE tweb_surat_format ADD kunci tinyint(1) NOT NULL DEFAULT '0'";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('favorit', 'tweb_surat_format'))
		{
			$query = "ALTER TABLE tweb_surat_format ADD favorit tinyint(1) NOT NULL DEFAULT '0'";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('id_pend', 'dokumen'))
		{
			$query = "ALTER TABLE dokumen ADD id_pend int(11) NOT NULL DEFAULT '0'";
			$this->db->query($query);
		}

		if (!$this->db->table_exists('setting_modul') )
		{
			$query = "
				CREATE TABLE `setting_modul` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`modul` varchar(50) NOT NULL,
					`url` varchar(50) NOT NULL,
					`aktif` tinyint(1) NOT NULL DEFAULT '0',
					`ikon` varchar(50) NOT NULL,
					`urut` tinyint(4) NOT NULL,
					`level` tinyint(1) NOT NULL DEFAULT '2',
					`hidden` tinyint(1) NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`)
					) ENGINE=".$this->engine." AUTO_INCREMENT=15 DEFAULT CHARSET=utf8
			";
			$this->db->query($query);

			$query = "
				INSERT INTO setting_modul VALUES
				('1','SID Home','hom_desa','1','go-home-5.png','1','2','1'),
				('2','Penduduk','penduduk/clear','1','preferences-contact-list.png','2','2','0'),
				('3','Statistik','statistik','1','statistik.png','3','2','0'),
				('4','Cetak Surat','surat','1','applications-office-5.png','4','2','0'),
				('5','Analisis','analisis_master/clear','1','analysis.png','5','2','0'),
				('6','Bantuan','program_bantuan','1','program.png','6','2','0'),
				('7','Persil','data_persil/clear','1','persil.png','7','2','0'),
				('8','Plan','plan','1','plan.png','8','2','0'),
				('9','Peta','gis','1','gis.png','9','2','0'),
				('10','SMS','sms','1','mail-send-receive.png','10','2','0'),
				('11','Pengguna','man_user/clear','1','system-users.png','11','1','1'),
				('12','Database','database','1','database.png','12','1','0'),
				('13','Admin Web','web','1','message-news.png','13','4','0'),
				('14','Laporan','lapor','1','mail-reply-all.png','14','2','0');
			";
			$this->db->query($query);
		}

		/**
			Sesuaikan data modul analisis dengan SID 3.10
		*/

		// Tabel analisis_indikator
		$ubah_kolom = array(
			"`nomor` int(3) NOT NULL"
		);
		foreach ($ubah_kolom as $kolom_def)
		{
			$query = "ALTER TABLE analisis_indikator MODIFY ".$kolom_def;
			$this->db->query($query);
		};
		if (!$this->db->field_exists('is_publik', 'analisis_indikator'))
		{
			$query = "ALTER TABLE analisis_indikator ADD `is_publik` tinyint(1) NOT NULL DEFAULT '0'";
			$this->db->query($query);
		}

		// Tabel analisis_kategori_indikator
		if (!$this->db->field_exists('kategori_kode', 'analisis_kategori_indikator'))
		{
			$query = "ALTER TABLE analisis_kategori_indikator ADD `kategori_kode` varchar(3) NOT NULL";
			$this->db->query($query);
		}

		// Tabel analisis_master
		if ($this->db->field_exists('kode_analiusis', 'analisis_master'))
		{
			$query = "ALTER TABLE analisis_master CHANGE `kode_analiusis` `kode_analisis` varchar(5) NOT NULL DEFAULT '00000'";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('id_child', 'analisis_master'))
		{
			$query = "ALTER TABLE analisis_master ADD `id_child` smallint(4) NOT NULL";
			$this->db->query($query);
		}

		// Tabel analisis_parameter
		if (!$this->db->field_exists('kode_jawaban', 'analisis_parameter'))
		{
			$query = "ALTER TABLE analisis_parameter ADD `kode_jawaban` int(3) NOT NULL";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('asign', 'analisis_parameter'))
		{
			$query = "ALTER TABLE analisis_parameter ADD `asign` tinyint(1) NOT NULL DEFAULT '0'";
			$this->db->query($query);
		}

		// Tabel analisis_respon
		$drop_kolom = array(
			"id",
			"tanggal_input"
		);
		foreach ($drop_kolom as $kolom_def){
			if ($this->db->field_exists($kolom_def, 'analisis_respon'))
			{
				$query = "ALTER TABLE analisis_respon DROP ".$kolom_def;
				$this->db->query($query);
			}
		};

		// Tabel analisis_respon_bukti
		$query = "
			CREATE TABLE IF NOT EXISTS `analisis_respon_bukti` (
				`id_master` tinyint(4) NOT NULL,
				`id_periode` tinyint(4) NOT NULL,
				`id_subjek` int(11) NOT NULL,
				`pengesahan` varchar(100) NOT NULL,
				`tgl_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
			) ENGINE=".$this->engine." DEFAULT CHARSET=utf8;
			";
		$this->db->query($query);

		// Tabel analisis_respon_hasil
		if ($this->db->field_exists('id', 'analisis_respon_hasil'))
		{
			$query = "ALTER TABLE analisis_respon_hasil DROP `id`";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('tgl_update', 'analisis_respon_hasil'))
		{
			$query = "ALTER TABLE analisis_respon_hasil ADD `tgl_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
			$this->db->query($query);
		}
		$db = $this->db->database;
		$query = "
			SELECT COUNT(1) ConstraintSudahAda
			FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
			WHERE TABLE_SCHEMA = ?
			AND TABLE_NAME = 'analisis_respon_hasil'
			AND CONSTRAINT_NAME = 'id_master'
		";
		$hasil = $this->db->query($query, $db);
		$data = $hasil->row_array();
		if ($data['ConstraintSudahAda'] == 0)
		{
			$query = "ALTER TABLE analisis_respon_hasil ADD CONSTRAINT `id_master` UNIQUE (`id_master`,`id_periode`,`id_subjek`)";
			$this->db->query($query);
		}

		/**
			Sesuaikan data modul persil dengan SID 3.10
		*/

		// Tabel data_persil
		$ubah_kolom = array(
			"`nik` varchar(64) NOT NULL",
			"`nama` varchar(128) NOT NULL COMMENT 'nomer persil'",
			"`persil_jenis_id` tinyint(2) NOT NULL",
			"`luas` decimal(7,2) NOT NULL",
			"`kelas` varchar(128) DEFAULT NULL",
			"`no_sppt_pbb` varchar(128) NOT NULL",
			"`persil_peruntukan_id` tinyint(2) NOT NULL"
		);
		foreach ($ubah_kolom as $kolom_def)
		{
			$query = "ALTER TABLE data_persil MODIFY ".$kolom_def;
			$this->db->query($query);
		};
		if (!$this->db->field_exists('peta', 'data_persil'))
		{
			$query = "ALTER TABLE data_persil ADD `peta` text";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('rdate', 'data_persil'))
		{
			$query = "ALTER TABLE data_persil ADD `rdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
			$this->db->query($query);
		}

		// Tabel data_persil_jenis
		$ubah_kolom = array(
			"`nama` varchar(128) NOT NULL",
			"`ndesc` text NOT NULL"
		);
		foreach ($ubah_kolom as $kolom_def)
		{
			$query = "ALTER TABLE data_persil_jenis MODIFY ".$kolom_def;
			$this->db->query($query);
		};

		// Tabel data_persil_peruntukan
		$ubah_kolom = array(
			"`nama` varchar(128) NOT NULL",
			"`ndesc` text NOT NULL"
		);
		foreach ($ubah_kolom as $kolom_def)
		{
			$query = "ALTER TABLE data_persil_peruntukan MODIFY ".$kolom_def;
			$this->db->query($query);
		};

		// Ubah surat keterangan pindah penduduk untuk bisa memilih format lampiran
		$query = "
			INSERT INTO `tweb_surat_format` (`id`, `url_surat`, `lampiran`) VALUES
			(5, 'surat_ket_pindah_penduduk', 'f-1.08.php,f-1.25.php')
			ON DUPLICATE KEY UPDATE
				url_surat = VALUES(url_surat),
				lampiran = VALUES(lampiran);
		";
		$this->db->query($query);
	}

	private function migrasi_111_ke_112()
	{
		// Ubah surat bio penduduk untuk menambah format lampiran
		$query = "
			INSERT INTO `tweb_surat_format` (`id`, `url_surat`, `lampiran`) VALUES
			(3, 'surat_bio_penduduk', 'f-1.01.php')
			ON DUPLICATE KEY UPDATE
				url_surat = VALUES(url_surat),
				lampiran = VALUES(lampiran);
		";
		$this->db->query($query);

		// Tabel tweb_penduduk melengkapi data F-1.01
		if (!$this->db->field_exists('telepon', 'tweb_penduduk'))
		{
			$query = "ALTER TABLE tweb_penduduk ADD `telepon` varchar(20)";
			$this->db->query($query);
		}
		if (!$this->db->field_exists('tanggal_akhir_paspor', 'tweb_penduduk'))
		{
			$query = "ALTER TABLE tweb_penduduk ADD `tanggal_akhir_paspor` date";
			$this->db->query($query);
		}

		// Ketinggalan tabel gis_simbol
		if (!$this->db->table_exists('gis_simbol') )
		{
			$query = "
				CREATE TABLE `gis_simbol` (
					`simbol` varchar(40) DEFAULT NULL
				) ENGINE=".$this->engine." DEFAULT CHARSET=utf8;
			";
			$this->db->query($query);
			// Isi dengan daftar icon yang ada di folder assets/images/gis/point
			$simbol_folder = FCPATH . 'assets/images/gis/point';
			$list_gis_simbol = scandir($simbol_folder);
			foreach ($list_gis_simbol as $simbol) {
				if ($simbol['0'] == '.') continue;
				$this->db->insert('gis_simbol', array('simbol' => $simbol));
			}
		}
		if (!$this->db->field_exists('jenis', 'tweb_surat_format'))
		{
			$query = "ALTER TABLE tweb_surat_format ADD jenis tinyint(2) NOT NULL DEFAULT 2";
			$this->db->query($query);
			// Update semua surat yang disediakan oleh rilis OpenSID
			$surat_sistem = array(
				'surat_ket_pengantar',
				'surat_ket_penduduk',
				'surat_bio_penduduk',
				'surat_ket_pindah_penduduk',
				'surat_ket_jual_beli',
				'surat_pindah_antar_kab_prov',
				'surat_ket_catatan_kriminal',
				'surat_ket_ktp_dalam_proses',
				'surat_ket_beda_nama',
				'surat_jalan',
				'surat_ket_kurang_mampu',
				'surat_izin_keramaian',
				'surat_ket_kehilangan',
				'surat_ket_usaha',
				'surat_ket_jamkesos',
				'surat_ket_domisili_usaha',
				'surat_ket_kelahiran',
				'surat_permohonan_akta',
				'surat_pernyataan_akta',
				'surat_permohonan_duplikat_kelahiran',
				'surat_ket_kematian',
				'surat_ket_lahir_mati',
				'surat_ket_nikah',
				'surat_ket_asalusul',
				'surat_persetujuan_mempelai',
				'surat_ket_orangtua',
				'surat_izin_orangtua',
				'surat_ket_kematian_suami_istri',
				'surat_kehendak_nikah',
				'surat_ket_pergi_kawin',
				'surat_ket_wali',
				'surat_ket_wali_hakim',
				'surat_permohonan_duplikat_surat_nikah',
				'surat_permohonan_cerai',
				'surat_ket_rujuk_cerai'
			);
			// Jenis surat yang bukan bagian rilis sistem sudah otomatis berisi nilai default (yaitu, 2)
			foreach ($surat_sistem as $url_surat)
			{
				$this->db->where('url_surat',$url_surat)->update('tweb_surat_format',array('jenis'=>1));
			}
		}
		// Tambah surat_permohonan_kartu_keluarga
		$this->db->where('url_surat', 'surat_ubah_sesuaikan')->update('tweb_surat_format',array('kode_surat' => 'P-01'));
		$query = "
			INSERT INTO tweb_surat_format (nama, url_surat, lampiran, kode_surat, jenis) VALUES
			('Permohonan Kartu Keluarga', 'surat_permohonan_kartu_keluarga', 'f-1.15.php', 'S-36', 1)
			ON DUPLICATE KEY UPDATE
				nama = VALUES(nama),
				url_surat = VALUES(url_surat),
				lampiran = VALUES(lampiran),
				kode_surat = VALUES(kode_surat),
				jenis = VALUES(jenis);
		";
		$this->db->query($query);
		// Tambah kolom no_kk_sebelumnya untuk penduduk yang pecah dari kartu keluarga
		if (!$this->db->field_exists('no_kk_sebelumnya', 'tweb_penduduk'))
		{
			$query = "ALTER TABLE tweb_penduduk ADD no_kk_sebelumnya varchar(30)";
			$this->db->query($query);
		}
	}

	public function kosongkan_db()
	{
		// Views tidak perlu dikosongkan.
		$views = array('daftar_kontak', 'daftar_anggota_grup', 'daftar_grup', 'penduduk_hidup');
		// Tabel dengan foreign key akan terkosongkan secara otomatis melalui delete
		// tabel rujukannya
		$ada_foreign_key = array('suplemen_terdata', 'kontak', 'anggota_grup_kontak', 'mutasi_inventaris_asset', 'mutasi_inventaris_gedung', 'mutasi_inventaris_jalan', 'mutasi_inventaris_peralatan', 'mutasi_inventaris_tanah', 'disposisi_surat_masuk', 'tweb_penduduk_mandiri', 'data_persil', 'setting_aplikasi_options', 'log_penduduk');
		$table_lookup = array(
			"analisis_ref_state",
			"analisis_ref_subjek",
			"analisis_tipe_indikator",
			"artikel", //remove everything except widgets 1003
			"gis_simbol",
			"media_sosial", //?
			"provinsi",
			"ref_pindah",
			"setting_modul",
			"setting_aplikasi",
			"setting_aplikasi_options",
			"skin_sid",
			"tweb_cacat",
			"tweb_cara_kb",
			"tweb_golongan_darah",
			"tweb_keluarga_sejahtera",
			"tweb_penduduk_agama",
			"tweb_penduduk_hubungan",
			"tweb_penduduk_kawin",
			"tweb_penduduk_pekerjaan",
			"tweb_penduduk_pendidikan",
			"tweb_penduduk_pendidikan_kk",
			"tweb_penduduk_sex",
			"tweb_penduduk_status",
			"tweb_penduduk_umur",
			"tweb_penduduk_warganegara",
			"tweb_rtm_hubungan",
			"tweb_sakit_menahun",
			"tweb_status_dasar",
			"tweb_status_ktp",
			"tweb_surat_format",
			"user",
			"user_grup",
			"widget"
		);

		// Hanya kosongkan contoh menu kalau pengguna memilih opsi itu
		if (empty($_POST['kosongkan_menu']))
		{
			array_push($table_lookup,"kategori","menu");
		}

		$jangan_kosongkan = array_merge($views, $ada_foreign_key, $table_lookup);

		// Hapus semua artikel kecuali artikel widget dengan kategori 1003
		$this->db->where("id_kategori !=", "1003");
		$query = $this->db->delete('artikel');
		// Kosongkan semua tabel kecuali table lookup dan views
		// Tabel yang ada foreign key akan dikosongkan secara otomatis
		$semua_table = $this->db->list_tables();
		foreach ($semua_table as $table)
		{
			if (!in_array($table, $jangan_kosongkan))
			{
				$query = "DELETE FROM " . $table . " WHERE 1";
				$this->db->query($query);
			}
		}
		// Tambahkan kembali Analisis DDK Profil Desa dan Analisis DAK Profil Desa
		$file_analisis = FCPATH . 'assets/import/analisis_DDK_Profil_Desa.xls';
		$this->analisis_import_model->import_excel($file_analisis, 'DDK02', $jenis = 1);
		$file_analisis = FCPATH . 'assets/import/analisis_DAK_Profil_Desa.xls';
		$this->analisis_import_model->import_excel($file_analisis, 'DAK02', $jenis = 1);

		$_SESSION['success'] = 1;
	}

}
?>
