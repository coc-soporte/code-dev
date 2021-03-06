<?php 


/**
* 
*/
class CheckList_model extends CI_model
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->database('bd_cded_cde_pda1');

		//$this->dbGTR = $this->load->database('gtr', true);
	}

	function validarSiExiste($tabla, $campo, $valor)
	{
		$string = "SELECT * FROM $tabla where $campo = '$valor'";
		//echo $string;
		$query = $this->db->query($string);
		if ($query) {
			return count($query->result_array());
		}
	}

	function getClientesSMS(){
		$query = $this->db->query("SELECT * FROM gtr.cliente");

		if ($query) {
			return json_encode($query->result_array(), JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		}
	}

	function getHorario($cod_pos = null)
	{
		if ($cod_pos == null) {
			$query = $this->db->query("SELECT * FROM bd_cded_cde_pda.horario_view");
		}else{
			$query = $this->db->query("SELECT * FROM bd_cded_cde_pda.horario_view
				where Cod_pos = '$cod_pos'");
		}
		

		return $query->result_array();

	}

	function getCheckListDeApertura($datetimeInicio, $datetimeFin, $filtro = null){
		
		$query = $this->db->query("SELECT REGIONAL, TIENDA,  SUM(check_list = 0) Impuntual, SUM(check_list = 3) Noregistro, SUM(check_list = 1) Puntual, SUM(check_list = 2) No_abre,   
			(SUM(check_list = 1)/SUM(check_list != 2))*100 Puntualidad
			FROM (
				SELECT fechaa, Dia, Apertura1, cast(ch_log as time) hora_ingreso, regional, Tienda,
					case when Apertura1 = 'null' or Apertura1 is NULL or Apertura1 = '' THEN 2
					WHEN CAST(ch_log as time) > ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 0
					WHEN ch_log IS NULL THEN 3
					WHEN CAST(ch_log as time) <= ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 1
					END AS Check_list
					FROM(
						SELECT * FROM (
							SELECT cast(ch_log as DATE) fechaa,
							CASE WHEN CAST(ch_log AS DATE) IN ('01-01-2014','06-01-2014','24-03-2014','17-04-2014','18-04-2014','01-05-2014','02-06-2014','23-06-2014','30-06-2014','30-06-2014','20-07-2014','07-08-2014','18-08-2014','13-10-2014','03-11-2014','17-11-2014','08-12-2014','25-12-2014','01-01-2015','12-01-2015','23-03-2015','02-04-2015','03-04-2015','01-05-2015','18-05-2015','08-06-2015','15-06-2015','29-06-2015','20-07-2015','07-08-2015','17-08-2015','12-10-2015','02-11-2015','16-11-2015','08-12-2015','25-12-2015','01-01-2016','11-01-2016','21-03-2016','24-03-2016','25-03-2016','01-05-2016','09-05-2016','30-05-2016','06-06-2016','04-07-2016','20-07-2016','07-08-2016','15-08-2016','17-10-2016','07-11-2016','14-11-2016','08-12-2016','25-12-2016','01-01-2017','09-01-2017','20-03-2017','13-04-2017','14-04-2017','01-05-2017','29-05-2017','19-06-2017','26-06-2017','03-07-2017','20-07-2017','07-08-2017','21-08-2017','16-10-2017','06-11-2017','13-11-2017','08-12-2017','25-12-2017')
							THEN 7 ELSE date_format(ch_log, '%w') END AS diaA
							FROM bd_cded_cde_pda.checklist
							WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
							AND cast(ch_log as DATE)               <= '$datetimeFin'
							GROUP BY FECHAa
						) AS HOLA
						JOIN (
							SELECT *, CASE WHEN Dia = 'Domingo' then 0 WHEN Dia = 'Lunes' then 1 WHEN Dia = 'Martes' then 2 WHEN Dia = 'Miercoles' then 3 WHEN Dia = 'Jueves' then 4 WHEN Dia = 'Viernes' then 5 WHEN Dia = 'Sabado' then 6 WHEN Dia = 'Festivos' then 7
							end as DiaNum
							FROM bd_cded_cde_pda.horario_view
							WHERE Tipo in ('Tienda Propia', 'corporativo')
						) AS PAPA
						ON HOLA.diaA = PAPA.DiaNum
						order by Tienda, fechaa
					) ANITA

					LEFT JOIN (
						SELECT * FROM (
							SELECT * FROM (
								SELECT  cd_id as id, ch_log, CAST(ch_log AS DATE) AS fecha_apertura, ch_usuario, ch_nombre, ch_codPos
									FROM bd_cded_cde_pda.checklist
									WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
									AND cast(ch_log as DATE)               <= '$datetimeFin'
							) THOR
							JOIN (
								SELECT FECHA, min(id2) as id2 FROM (
									SELECT cd_id as id2, CAST(ch_log AS DATE) fecha, ch_log, ch_usuario, ch_nombre, ch_codPos
									FROM bd_cded_cde_pda.checklist
									WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
									AND cast(ch_log as DATE)               <= '$datetimeFin'
									ORDER BY ch_log DESC
								) MAMA
								GROUP BY FECHA, ch_codPos
							) ODIN
							ON THOR.id = ODIN.id2
						) TAB
					) AS COC

				ON ANITA.FECHAa = COC.FECHA_APERTURA AND ANITA.Cod_Pos = COC.ch_codPos
				ORDER BY fechaa
			) AS CONSULTA_CKECK
			$filtro
			GROUP BY REGIONAL, TIENDA
			ORDER BY REGIONAL, PUNTUALIDAD DESC, TIENDA");

		return $query->result_array();
	}

	function getCheckListDeApertura22($datetimeInicio, $datetimeFin, $filtro = null)
	{

		$query = $this->db->query("SELECT REGIONAL, TIENDA,  SUM(check_list = 0) Impuntual, SUM(check_list = 3) Noregistro, SUM(check_list = 1) Puntual, SUM(check_list = 2) No_abre,   
			(SUM(check_list = 1)/SUM(check_list != 2))*100 Puntualidad
			FROM (
				SELECT fechaa, DiaNum, Cod_pos, Dia, Apertura1, Cierre1, fecha_sis, regional, Tienda, ph_ase_total, ph_ase_segturno,
				case when Apertura1 = 'null' or Apertura1 is NULL or Apertura1 = '' THEN 2
				WHEN CAST(fecha_sis as time) > ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 0
				WHEN fecha_sis IS NULL THEN 3
				WHEN CAST(fecha_sis as time) <= ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 1
				END AS Check_list
				FROM(
					SELECT * FROM (
						SELECT cast(fecha_sis as DATE) fechaa,
						CASE WHEN CAST(fecha_sis AS DATE) IN ('01-01-2014','06-01-2014','24-03-2014','17-04-2014','18-04-2014','01-05-2014','02-06-2014','23-06-2014','30-06-2014','30-06-2014','20-07-2014','07-08-2014','18-08-2014','13-10-2014','03-11-2014','17-11-2014','08-12-2014','25-12-2014','01-01-2015','12-01-2015','23-03-2015','02-04-2015','03-04-2015','01-05-2015','18-05-2015','08-06-2015','15-06-2015','29-06-2015','20-07-2015','07-08-2015','17-08-2015','12-10-2015','02-11-2015','16-11-2015','08-12-2015','25-12-2015','01-01-2016','11-01-2016','21-03-2016','24-03-2016','25-03-2016','01-05-2016','09-05-2016','30-05-2016','06-06-2016','04-07-2016','20-07-2016','07-08-2016','15-08-2016','17-10-2016','07-11-2016','14-11-2016','08-12-2016','25-12-2016','01-01-2017','09-01-2017','20-03-2017','13-04-2017','14-04-2017','01-05-2017','29-05-2017','19-06-2017','26-06-2017','03-07-2017','20-07-2017','07-08-2017','21-08-2017','16-10-2017','06-11-2017','13-11-2017','08-12-2017','25-12-2017')
						THEN 7 ELSE date_format(cast(fecha_sis as DATE), '%w') END AS diaA
						FROM cocinfo.u_dmsapertura
						WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
						AND cast(fecha_sis as DATE)               <= '$datetimeFin'
						AND info_canal                             = 'Tiendas Propias'
						GROUP BY FECHAa) AS HOLA
JOIN (
	SELECT *, CASE WHEN Dia = 'Domingo' then 0 WHEN Dia = 'Lunes' then 1 WHEN Dia = 'Martes' then 2 WHEN Dia = 'Miercoles' then 3 WHEN Dia = 'Jueves' then 4 WHEN Dia = 'Viernes' then 5 WHEN Dia = 'Sabado' then 6 WHEN Dia = 'Festivos' then 7
	end as DiaNum
	FROM bd_cded_cde_pda.horario_view
	WHERE Tipo in ('Tienda Propia', 'corporativo')) AS PAPA
ON HOLA.diaA = PAPA.DiaNum
order by Tienda, fechaa) ANITA

LEFT JOIN (

	SELECT * FROM
	(SELECT * FROM
		(SELECT  id, fecha_sis, CAST(fecha_sis AS DATE) AS fecha_apertura, usuario_id, usuario, info_regional, info_cde, ph_ase_total, ph_ase_segturno , info_canal
			FROM cocinfo.u_dmsapertura
			WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
			AND cast(fecha_sis as DATE)               <= '$datetimeFin'
			AND info_canal                             = 'Tiendas Propias'
			) THOR
JOIN
(SELECT FECHA,
	id2
	FROM
	(SELECT id as id2, CAST(fecha_sis AS DATE) fecha, fecha_sis, fecha_apertura, usuario_id, usuario, info_regional, info_cde, ph_ase_total, ph_ase_segturno , info_canal
		FROM cocinfo.u_dmsapertura
		WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
		AND cast(fecha_sis as DATE)               <= '$datetimeFin'
		AND info_canal                             = 'Tiendas Propias'
		ORDER BY fecha_sis DESC
		) MAMA
GROUP BY FECHA,
info_cde
) ODIN
ON THOR.id = ODIN.id2
) TAB
) AS COC
ON ANITA.FECHAa = COC.FECHA_APERTURA AND ANITA.TIENDA = COC.INFO_CDE
ORDER BY fechaa ) AS CONSULTA_CKECK
$filtro
GROUP BY REGIONAL, TIENDA
ORDER BY REGIONAL, PUNTUALIDAD DESC, TIENDA");

return $query->result_array();

}

function getCheckListPorCDE($datetimeInicio, $datetimeFin, $CDE){

	$query = $this->db->query("SELECT fechaa, Dia, Apertura1, cast(ch_log as time) hora_ingreso, regional, Tienda,
		case when Apertura1 = 'null' or Apertura1 is NULL or Apertura1 = '' THEN 'No abre'
		WHEN CAST(ch_log as time) > ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 'Impuntual'
		WHEN ch_log IS NULL THEN 'No montó checklist'
		WHEN CAST(ch_log as time) <= ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 'Puntual'
		END AS Check_list
		FROM(
			SELECT * FROM (
				SELECT cast(ch_log as DATE) fechaa,
				CASE WHEN CAST(ch_log AS DATE) IN ('01-01-2014','06-01-2014','24-03-2014','17-04-2014','18-04-2014','01-05-2014','02-06-2014','23-06-2014','30-06-2014','30-06-2014','20-07-2014','07-08-2014','18-08-2014','13-10-2014','03-11-2014','17-11-2014','08-12-2014','25-12-2014','01-01-2015','12-01-2015','23-03-2015','02-04-2015','03-04-2015','01-05-2015','18-05-2015','08-06-2015','15-06-2015','29-06-2015','20-07-2015','07-08-2015','17-08-2015','12-10-2015','02-11-2015','16-11-2015','08-12-2015','25-12-2015','01-01-2016','11-01-2016','21-03-2016','24-03-2016','25-03-2016','01-05-2016','09-05-2016','30-05-2016','06-06-2016','04-07-2016','20-07-2016','07-08-2016','15-08-2016','17-10-2016','07-11-2016','14-11-2016','08-12-2016','25-12-2016','01-01-2017','09-01-2017','20-03-2017','13-04-2017','14-04-2017','01-05-2017','29-05-2017','19-06-2017','26-06-2017','03-07-2017','20-07-2017','07-08-2017','21-08-2017','16-10-2017','06-11-2017','13-11-2017','08-12-2017','25-12-2017')
				THEN 7 ELSE date_format(ch_log, '%w') END AS diaA
				FROM bd_cded_cde_pda.checklist
				WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
				AND cast(ch_log as DATE)               <= '$datetimeFin'
				GROUP BY FECHAa
			) AS HOLA
			JOIN (
				SELECT *, CASE WHEN Dia = 'Domingo' then 0 WHEN Dia = 'Lunes' then 1 WHEN Dia = 'Martes' then 2 WHEN Dia = 'Miercoles' then 3 WHEN Dia = 'Jueves' then 4 WHEN Dia = 'Viernes' then 5 WHEN Dia = 'Sabado' then 6 WHEN Dia = 'Festivos' then 7
				end as DiaNum
				FROM bd_cded_cde_pda.horario_view
				WHERE Tipo in ('Tienda Propia', 'corporativo')
			) AS PAPA
			ON HOLA.diaA = PAPA.DiaNum
			order by Tienda, fechaa
		) ANITA

		LEFT JOIN (
			SELECT * FROM (
				SELECT * FROM (
					SELECT  cd_id as id, ch_log, CAST(ch_log AS DATE) AS fecha_apertura, ch_usuario, ch_nombre, ch_codPos
						FROM bd_cded_cde_pda.checklist
						WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
						AND cast(ch_log as DATE)               <= '$datetimeFin'
				) THOR
				JOIN (
					SELECT FECHA, min(id2) as id2 FROM (
						SELECT cd_id as id2, CAST(ch_log AS DATE) fecha, ch_log, ch_usuario, ch_nombre, ch_codPos
						FROM bd_cded_cde_pda.checklist
						WHERE cast(ch_log as DATE)             >= '$datetimeInicio'
						AND cast(ch_log as DATE)               <= '$datetimeFin'
						ORDER BY ch_log DESC
					) MAMA
					GROUP BY FECHA, ch_codPos
				) ODIN
				ON THOR.id = ODIN.id2
			) TAB
		) AS COC

	ON ANITA.FECHAa = COC.FECHA_APERTURA AND ANITA.Cod_Pos = COC.ch_codPos
	where Tienda = '$CDE'
	ORDER BY fechaa");
	return $query->result_array();
	
}

function getCheckListPorCDE22($datetimeInicio, $datetimeFin, $CDE){

	$query = $this->db->query("	SELECT fechaa, Dia, Apertura1, cast(fecha_sis as time) hora_ingreso, regional, Tienda,
		case when Apertura1 = 'null' or Apertura1 is NULL or Apertura1 = '' THEN 'No abre'
		WHEN CAST(fecha_sis as time) > ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 'Impuntual'
		WHEN fecha_sis IS NULL THEN 'No montó checklist'
		WHEN CAST(fecha_sis as time) <= ADDTIME(str_to_date(CONCAT( CAST(Apertura1 as time) , ' ', SUBSTRING(Apertura1, -2)),'%r'), '00:31:00') THEN 'Puntual'
		END AS Check_list
		FROM(
			SELECT * FROM (
				SELECT cast(fecha_sis as DATE) fechaa,
				CASE WHEN CAST(fecha_sis AS DATE) IN ('01-01-2014','06-01-2014','24-03-2014','17-04-2014','18-04-2014','01-05-2014','02-06-2014','23-06-2014','30-06-2014','30-06-2014','20-07-2014','07-08-2014','18-08-2014','13-10-2014','03-11-2014','17-11-2014','08-12-2014','25-12-2014','01-01-2015','12-01-2015','23-03-2015','02-04-2015','03-04-2015','01-05-2015','18-05-2015','08-06-2015','15-06-2015','29-06-2015','20-07-2015','07-08-2015','17-08-2015','12-10-2015','02-11-2015','16-11-2015','08-12-2015','25-12-2015','01-01-2016','11-01-2016','21-03-2016','24-03-2016','25-03-2016','01-05-2016','09-05-2016','30-05-2016','06-06-2016','04-07-2016','20-07-2016','07-08-2016','15-08-2016','17-10-2016','07-11-2016','14-11-2016','08-12-2016','25-12-2016','01-01-2017','09-01-2017','20-03-2017','13-04-2017','14-04-2017','01-05-2017','29-05-2017','19-06-2017','26-06-2017','03-07-2017','20-07-2017','07-08-2017','21-08-2017','16-10-2017','06-11-2017','13-11-2017','08-12-2017','25-12-2017')
				THEN 7 ELSE date_format(fecha_apertura, '%w') END AS diaA
				FROM cocinfo.u_dmsapertura
				WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
				AND cast(fecha_sis as DATE)               <= '$datetimeFin'
				AND info_canal                             = 'Tiendas Propias'
				GROUP BY FECHAa) AS HOLA
	JOIN (
		SELECT *, CASE WHEN Dia = 'Domingo' then 0 WHEN Dia = 'Lunes' then 1 WHEN Dia = 'Martes' then 2 WHEN Dia = 'Miercoles' then 3 WHEN Dia = 'Jueves' then 4 WHEN Dia = 'Viernes' then 5 WHEN Dia = 'Sabado' then 6 WHEN Dia = 'Festivos' then 7
		end as DiaNum
		FROM bd_cded_cde_pda.horario_view
		WHERE Tipo in ('Tienda Propia', 'corporativo')) AS PAPA
	ON HOLA.diaA = PAPA.DiaNum
	order by Tienda, fechaa) ANITA

	LEFT JOIN (

		SELECT * FROM
		(SELECT * FROM
			(SELECT  id, fecha_sis, CAST(fecha_sis AS DATE) AS fecha_apertura, usuario_id, usuario, info_regional, info_cde, ph_ase_total, ph_ase_segturno , info_canal
				FROM cocinfo.u_dmsapertura
				WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
				AND cast(fecha_sis as DATE)               <= '$datetimeFin'
				AND info_canal                             = 'Tiendas Propias'
				) THOR
	JOIN
	(SELECT FECHA,
		id2
		FROM
		(SELECT id as id2, CAST(fecha_sis AS DATE) fecha, fecha_sis, fecha_apertura, usuario_id, usuario, info_regional, info_cde, ph_ase_total, ph_ase_segturno , info_canal
			FROM cocinfo.u_dmsapertura
			WHERE cast(fecha_sis as DATE)             >= '$datetimeInicio'
			AND cast(fecha_sis as DATE)               <= '$datetimeFin'
			AND info_canal                             = 'Tiendas Propias'
			ORDER BY fecha_sis DESC
			) MAMA
	GROUP BY FECHA,
	info_cde
	) ODIN
	ON THOR.id = ODIN.id2
	) TAB
	) AS COC
	ON ANITA.FECHAa = COC.FECHA_APERTURA AND ANITA.TIENDA = COC.INFO_CDE
	where Tienda = '$CDE'
	ORDER BY fechaa");

	return $query->result_array();
}



function getIP($oficina)
{
		//$BDCentral = $this->load->database();
		//echo $oficina;
	$oficina = str_replace("-", " ", $oficina);
	$oficina = "%" . $oficina;

	$query = $this->db->query("SELECT * FROM bd_cded_cde_pda.dg45_servidores_rep
		where SER_SDSTRDESCRIPCION like '$oficina'");
		//echo "<pre>"; print_r($query->row_array()); echo "</pre>";
	if (!$query) {
		$query = 0;
			//echo "se generó una mala consulta";
	}
	else
	{
		return $query->row_array();
	}
}

function getIPSmysql(){
	$query = $this->db->query("SELECT iplist FROM bd_cded_cde_pda.iplist");
	
	if ($query) {
		$resultado = $query->row_array();
		return $resultado['iplist'];
	}

}

function setIPSmysql($data){
	$data = array('iplist' => $data);

	$this->db->where('id', 1);
	$this->db->update('iplist', $data);
}


function getListaNombresCDEs()
{
	$query = $this->db->query("SELECT SER_SDSTRDESCRIPCION as cde FROM bd_cded_cde_pda.dg45_servidores_rep");

	if (!$query) {
		$query = 0;
			//echo "se generó una mala consulta";
	}
	else
	{
		return $query->result_array();
	}
}

function getInfoCDE($Cod_pos){

	$query = $this->db->query("SELECT * FROM bd_cded_cde_pda.tiendas as tiend
		join bd_cded_cde_pda.horarios as hor
		on hor.Tiendas_cod_pos = tiend.Cod_Pos
		where Cod_Pos = '$Cod_pos'
		and Dia = 'lunes'");

	if (!$query) {
		$query = 0;
			//echo "se generó una mala consulta";
	}
	else
	{
		return $query->row_array();
	}
}

function getInfoCDEadmin($Cos_pos){

	$query = $this->db->query("SELECT id_admin, Cod_Pos, Regional, Tienda, Identificacion, Nombre, Apellido, 
		Movil_1, Movil_2 as Movil_2, Correo FROM bd_cded_cde_pda.tiendas as tiend
		join  bd_cded_cde_pda.administradores as admin
		on admin.Tiendas_cod_pos = tiend.Cod_Pos
		where Cod_Pos = '$Cos_pos'");

	if (!$query) {
		$query = 0; //echo "se generó una mala consulta";
	} else{
		return $query->result_array();
	}

}

function getInfoCDEcoor($Cod_pos){

	$query = $this->db->query("SELECT id, Cod_Pos, Regional, Tienda, Identificacion, Nombre, Apellido, 
		Movil_1, Movil_2 as Movil_2, Correo FROM bd_cded_cde_pda.tiendas as tiend
		join  bd_cded_cde_pda.coordinadores_db as coor
		on coor.Tiendas_cod_pos = tiend.Cod_Pos
		where Cod_Pos = '$Cod_pos'");

	if (!$query) {
		$query = 0; //echo "se generó una mala consulta";
	} else{
		return $query->result_array();
	}
}

function getCDElist(){
	$query =  $this->db->query("SELECT Cod_Pos, Regional, Ciudad, Tienda FROM bd_cded_cde_pda.tiendas");
	if (!$query) {
		$query = 0; //echo "se generó una mala consulta";
	} else{
		return $query->result_array();
	}
}


function get_hvc($celular){

	$query = $this->db->query("SELECT * FROM bd_cded_cde_pda.hvc WHERE MSISDN = '$celular'");
	if ($query) {
		$resultado = json_encode($query->row_array(), JSON_UNESCAPED_UNICODE);
		return $resultado;

	}
}

function getDatosApertura(){
	$string = "SELECT * FROM ( 
			SELECT Cod_Pos, Tienda FROM bd_cded_cde_pda.tiendas) as tie
	join (SELECT u_dmsapertura.id,
		u_dmsapertura.fecha_sis,
		u_dmsapertura.usuario,
		u_dmsapertura.info_regional,
		u_dmsapertura.info_cde,
		u_dmsapertura.info_observaciones,
		u_dmsapertura.ph_contratado,
		u_dmsapertura.ph_ase_total,
		u_dmsapertura.ph_ase_segturno,
		u_dmsapertura.ph_ase_incapacitados,
		u_dmsapertura.ph_ase_compensando,
		u_dmsapertura.ph_ase_ausente,
		u_dmsapertura.ph_ase_vacaciones,
		u_dmsapertura.ph_prestado,
		u_dmsapertura.ph_vacioLab
		FROM cocinfo.u_dmsapertura
		where info_canal != 'Tiendas Dealers'
		and info_regional = 'costa'
		AND cast(fecha_sis as date)  = '2014-08-08') as aper
	on tie.Tienda = aper.info_cde
	order by fecha_sis";

	$query = $this->db->query($string);
	if ($query) {
		return json_encode($query->result_array(), JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}
}

function actividadWorstOffenderModel(){

	$fechaInicial = $this->input->get('fechaInicial') . ' 08:00:00';
	$fechaFinal = $this->input->get('fechaFinal') . ' 22:00:00';

	if ($fechaInicial and $fechaFinal) {
		$query = $this->db->query("SELECT * FROM (
			SELECT fecha, UPPER(nombre) nombre, labor, ISNULL(SUM(tiempo),0) as tiempo_Labor, cargo, sucursal, regional
				FROM [dbo].[Actividad_por_hora] ('$fechaInicial','$fechaFinal')
				WHERE NOMBRE != 'SELECTOR' -- AND LABOR = 'Disponible'
				GROUP BY fecha, nombre, labor, cargo, sucursal, regional		
		) AS hello
		ORDER BY LABOR, tiempo_Labor DESC");
	
		if ($query) {
			$resultado = json_encode($query->row_array(), JSON_UNESCAPED_UNICODE);
			return $resultado;

		}
	}
}

	function getCheckListCDE($Cod_Pos, $paginacion){
		
		$query = "SELECT * FROM bd_cded_cde_pda.checklist
				where ch_codPos = '$Cod_Pos'
				order by ch_log desc
				limit " . $paginacion . ", 10";

		$resultado = $this->db->query($query);
		if ($resultado) {
			return json_encode($resultado->result_array(), JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		}


	}

	function getCheckListCDEid($Cod_Pos, $id){
		
		$query = "SELECT * FROM bd_cded_cde_pda.checklist
				where cd_id = '$id' and ch_codPos = '$Cod_Pos' ";

		$resultado = $this->db->query($query);
		if ($resultado) {
			return json_encode($resultado->row_array(), JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		}


	}




	/**
	U P D A T E S
*/
	function setHorario($Cod_Pos, $Dia, $nombreColumna)
	{
		$hora = $this->input->post('hora-text');
		$string = "UPDATE  bd_cded_cde_pda.horarios 
		SET $nombreColumna = '$hora'
		where Tiendas_Cod_Pos = '$Cod_Pos' and Dia = '$Dia'";
		
		$query = $this->db->query($string);
		if ($query) {
			return 1;
		}else{
			return 0;
		}
	}


	function updateDataCDE($Cod_Pos)
	{
		$Cod_Pos2 = $this->input->post('Cod_Pos');
		$Regional = $this->input->post('regional');
		$Ciudad = $this->input->post('ciudad');
		$Tipo = $this->input->post('tipo');
		$Version = $this->input->post('version');
		$ClasificacionCDE = $this->input->post('clasificacion');
		$Direccion = $this->input->post('direccion');

		$string = "UPDATE  bd_cded_cde_pda.tiendas 
		SET Cod_Pos = '$Cod_Pos2', Regional = '$Regional', Ciudad = '$Ciudad', Tipo = '$Tipo', 
		Version = '$Version', ClasificacionCDE = '$ClasificacionCDE', Direccion = '$Direccion'
		where Cod_Pos = '$Cod_Pos2'";

		//$this->db->where('Cod_Pos', $Cod_Pos);
		//$query = $this->db->update('tiendas', $data, array('Cod_Pos' => $Cod_Pos ));
		$query = $this->db->query($string);

		if ($query) {
			return 1;
		}else{
			return 0;
		}
	}

	function updateDataCoor($Cod_Pos, $Identificacion)
	{
		$nombre = $this->input->post('nombreCor');
		$apellido = $this->input->post('ApellidoCor');
		$identificacion = $this->input->post('identificacion');
		$Movil_1 = $this->input->post('CelCor');
		$Movil_1hidden = $this->input->post('CelCorHidden');

		$Movil_2 = $this->input->post('CelCor2');
		$correo = $this->input->post('emailCor');

		$id = $this->input->post('id');

			//$Cod_Pos2 = $this->input->post('clasificacion');

		$existe = $this->validarSiExiste('gtr.cliente', 'CELULAR', $Movil_1hidden);
		$nombreCompleto = strtoupper($nombre . ' ' . $apellido);
		
		if ($existe == 1) {
			
			$stringAdmCDE = "UPDATE gtr.cliente
			SET CELULAR = '$Movil_1', NOMBRE = '$nombreCompleto'
			WHERE CELULAR = '$Movil_1hidden'";
			echo $stringAdmCDE;

			$query2 = $this->db->query($stringAdmCDE);

		}elseif($existe == 0){

			$stringAdmCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE)
			VALUES
			('$Movil_1','$nombreCompleto','COORDINADOR CDE','ADM')";
			echo $stringAdmCDE;
			$query2 = $this->db->query($stringAdmCDE);
		}

		$string = "UPDATE  bd_cded_cde_pda.coordinadores_db 
		SET Nombre = '$nombre', Apellido = '$apellido', Identificacion = '$identificacion', 
		Movil_1 = '$Movil_1', Movil_2 = '$Movil_2', Correo = '$correo'
		where id = '$id'";

		//$this->db->where('Cod_Pos', $Cod_Pos);
		//$query = $this->db->update('tiendas', $data, array('Cod_Pos' => $Cod_Pos ));
		$query = $this->db->query($string);

		if ($query  && $query2) {
			return 1;
		}else{
			return 0;
		}
	}

	function updateCDEAdmin($id_admin, $Cod_PosOld, $Cod_posNuevo){
		$string = "UPDATE  bd_cded_cde_pda.administradores SET Tiendas_Cod_Pos = '$Cod_posNuevo' where Tiendas_Cod_Pos = '$Cod_PosOld' and id_admin = '$id_admin'";
		$query = $this->db->query($string);
		if ($query) {return 1;}else{return 0;}
	}

	function updateCDECoor($id_coor, $Cod_PosOld, $Cod_posNuevo){
		$string = "UPDATE  bd_cded_cde_pda.coordinadores_db SET Tiendas_Cod_Pos = '$Cod_posNuevo' where Tiendas_Cod_Pos = '$Cod_PosOld' and id = '$id_coor'";
		$query = $this->db->query($string);
		if ($query) {return 1;}else{return 0;}
	}

	function updateDataAdmin($Cod_Pos)
	{
		$nombre = $this->input->post('nombreAdmin');
		$apellido = $this->input->post('apellidoAdmin');
		$identificacion = $this->input->post('identificacionAdmin');
		$Movil_1 = $this->input->post('CelAdmin');

		$Movil_1hidden = $this->input->post('CelAdminHiden');
			//$Movil_2 = $this->input->post('tipo');
		$correo = $this->input->post('emailAdmin');
			//$Cod_Pos2 = $this->input->post('clasificacion');
		$existe = $this->validarSiExiste('gtr.cliente', 'CELULAR', $Movil_1hidden);

		$nombreCompleto = strtoupper($nombre . ' ' . $apellido);
		
		if ($existe == 1) {
			
			$stringAdmCDE = "UPDATE gtr.cliente
			SET CELULAR = '$Movil_1', NOMBRE = '$nombreCompleto'
			WHERE CELULAR = '$Movil_1hidden'";
			//echo $stringAdmCDE;

			$query2 = $this->db->query($stringAdmCDE);

		}elseif($existe == 0){

			$stringAdmCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE)
			VALUES
			('$Movil_1','$nombreCompleto','ADMINISTRADOR','ADM')";
			//echo $stringAdmCDE;
			$query2 = $this->db->query($stringAdmCDE);
		}

		$string = "UPDATE  bd_cded_cde_pda.administradores 
		SET Nombre = '$nombre', Apellido = '$apellido', Identificacion = '$identificacion', 
		Movil_1 = '$Movil_1', Correo = '$correo'
		where Tiendas_Cod_Pos = '$Cod_Pos'";
		//echo $string;
		//$this->db->where('Cod_Pos', $Cod_Pos);
		//$query = $this->db->update('tiendas', $data, array('Cod_Pos' => $Cod_Pos ));
		$query = $this->db->query($string);

		if ($query && $query2) {
			return 1;
		}else{
			return 0;
		}
	}
	/**
	I N S E R T
	*/
	function setIp(){
		$data = array('ip' => $_SERVER['REMOTE_ADDR'],
					'user_agent' => $_SERVER['HTTP_USER_AGENT']);

		$this->db->insert('ipcliente', $data); 
	}

	function setClienteSMS(){
		//$data = $this->input->post();

		$llamada = file_get_contents('php://input');
		//echo "<pre>"; print_r(json_decode($data, TRUE)); echo "</pre>";

		$data = json_decode($llamada, TRUE);

		$query = $this->dbGTR->insert('cliente', $data);
		if ($query) {
			echo "1";
		}else{
			echo "0";
		}
	}

	function setAdmin($Cod_Pos){
		$dataAdmin = array(
			'Tiendas_Cod_Pos' => $Cod_Pos,
			'Identificacion' => $this->input->post('identificacionAdmin') ,
			'Nombre' => $this->input->post('nombreAdmin') ,
			'Apellido' => $this->input->post('apellidoAdmin'),
			'Movil_1' => $this->input->post('CelAdmin'),
			'Movil_2' => $this->input->post('CelAdmin2'),
			'Correo' => $this->input->post('emailAdmin')
		);

		$Movil_1 = $dataAdmin['Movil_1'];
		$existe = $this->validarSiExiste('gtr.cliente', 'CELULAR', $Movil_1);
		$nombreCompleto = strtoupper($this->input->post('nombreAdmin') . ' ' . $this->input->post('apellidoAdmin'));
		
		if($existe == 0){

			$stringAdmCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE)
			VALUES
			('$Movil_1','$nombreCompleto','ADMINISTRADOR CDE','ADM')";
			//echo $stringAdmCDE;
			$query2 = $this->db->query($stringAdmCDE);
		}
		
		//echo "<pre>"; print_r($dataAdmin); echo "</pre>";

		$query = $this->db->insert('administradores', $dataAdmin);
		if ($query) {return 1;}else{return 0;} 
	}

	function setCoor($Cod_Pos){

		$data = array(
			'Tiendas_Cod_Pos' =>  $Cod_Pos,
			'Identificacion' => $this->input->post('identificacion') ,
			'Nombre' => $this->input->post('nombreCor') ,
			'Apellido' => $this->input->post('ApellidoCor'),
			'Movil_1' => $this->input->post('CelCor'),
			'Movil_2' => $this->input->post('CelCor2'),
			'Correo' => $this->input->post('emailCor')
			);

		$Movil_1 = $data['Movil_1'];
		$existe = $this->validarSiExiste('gtr.cliente', 'CELULAR', $Movil_1);
		$nombreCompleto = strtoupper($this->input->post('nombreCor') . ' ' . $this->input->post('ApellidoCor'));
		
		if($existe == 0){

			$stringAdmCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE)
			VALUES
			('$Movil_1','$nombreCompleto','COORDINADOR CDE','ADM')";
			//echo $stringAdmCDE;
			$query2 = $this->db->query($stringAdmCDE);
		}
		//echo "<pre>"; print_r($data); echo "</pre>";

		$this->db->insert('coordinadores_db', $data); 
	}

	function setCDE(){

		$dataCDE = array(
			'Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Tienda' => $this->input->post('nombreTienda'),
			'Regional' => $this->input->post('regional') ,
			'Ciudad' => $this->input->post('ciudad') ,
			'Tipo' => $this->input->post('tipo'),
			'Version' => $this->input->post('version'),
			'ClasificacionCDE' => $this->input->post('clasificacion'),
			'Direccion' => $this->input->post('direccion')              
			);
		$dataAdmin = array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Identificacion' => $this->input->post('identificacionAdmin') ,
			'Nombre' => $this->input->post('nombreAdmin') ,
			'Apellido' => $this->input->post('apellidoAdmin'),
			'Movil_1' => $this->input->post('CelAdmin'),
			'Movil_2' => $this->input->post('CelAdmin2'),
			'Correo' => $this->input->post('emailAdmin')
			);

		// ****************************************************************+
		$CELULAR = $dataAdmin['Movil_1'];
		$NOMBRE = $dataAdmin['Nombre'] . ' ' . $dataAdmin['Apellido'];
		$CARGO = 'ADMINISTRADOR CDE';
		$TIPO_CLIENTE = 'ADM';
		$COD_CDE = $dataAdmin['Tiendas_Cod_Pos'];
		$REGIONAL = $dataCDE['Regional'];
		$CDE = strtoupper($dataCDE['Tienda']);

		$stringAdmCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE,COD_CDE,REGIONAL)
			VALUES
			('$CELULAR','$NOMBRE','$CARGO','$TIPO_CLIENTE','$COD_CDE','$REGIONAL')";

		$stringInfoCDESMS = "INSERT INTO gtr.infocde
			(CODPOS,REGION,CDE,ACTIVO)
			VALUES
			('$COD_CDE', '$REGIONAL', '$CDE','1')";
		
		// *******************************************************************+

		$dataCoor = array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Identificacion' => $this->input->post('identificacionCoor') ,
			'Nombre' => $this->input->post('nombreCoor') ,
			'Apellido' => $this->input->post('apellidoCoor'),
			'Movil_1' => $this->input->post('CelCoor'),
			'Movil_2' => $this->input->post('CelAdmin2'),
			'Correo' => $this->input->post('CelCoor2')
			);

		$CELULARCor = $this->input->post('CelCoor');
		$NOMBRECor = $this->input->post('nombreCoor') . ' ' . $this->input->post('apellidoCoor');
		$CARGOCor = 'COORDINADOR CDE';

		$stringCorCDE = "INSERT INTO gtr.cliente
			(CELULAR,NOMBRE,CARGO,TIPO_CLIENTE,COD_CDE,REGIONAL)
			VALUES
			('$CELULARCor','$NOMBRECor','$CARGOCor','$TIPO_CLIENTE','$COD_CDE','$REGIONAL')";
		//*************************************************************************************


		$dataHorario = array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Lunes',
			'Horario' => $this->input->post('horario')
			);

		$dataRestoHorario = array(
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Martes'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Miercoles'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Jueves'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Viernes'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Sabado'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Domingo'
			),
		array(
			'Tiendas_Cod_Pos' => $this->input->post('Cod_Pos') ,
			'Dia' => 'Festivos'
			),
		);

		$this->db->insert('tiendas', $dataCDE);

		$queryInfoCDESMS = $this->db->query($stringInfoCDESMS);
		if ($queryInfoCDESMS) {
			$this->db->query($stringAdmCDE);
			$this->db->query($stringCorCDE);
		}

		//$this->db->insert('administradores', $dataAdmin);		
		//$this->db->insert('coordinadores_db', $dataCoor);
		$this->db->insert('horarios', $dataHorario);
		$this->db->insert_batch('horarios', $dataRestoHorario);


		return $dataCDE['Cod_Pos'];
	}

	 function insertChecklist($data){
		
		$this->db->insert('checklist', $data);
    }

	/**
	D E L E T E S
	*/
	function deleteAdmin($id){
		
		$query1 = $this->db->query("SELECT Movil_1 FROM bd_cded_cde_pda.administradores where id_admin = '$id'");
		if ($query1) {
			$data = $query1->row_array();
			$celular = $data['Movil_1'];
			$this->db->query("DELETE FROM gtr.cliente where CELULAR = '$celular'");

			$this->db->query("DELETE FROM bd_cded_cde_pda.administradores where id_admin = '$id'");
		}
		
	}

	function deleteCoor($id){

		$query1 = $this->db->query("SELECT Movil_1 FROM bd_cded_cde_pda.coordinadores_db where id = '$id'");
		if ($query1) {
			$data = $query1->row_array();
			$celular = $data['Movil_1'];
			$this->db->query("DELETE FROM gtr.cliente where CELULAR = '$celular'");

			$this->db->query("DELETE FROM bd_cded_cde_pda.coordinadores_db where id = '$id'");
		}
		
	}

	function deleteCDEall($cod_pos){

		if ($cod_pos == '') {
			return;
		}

		$this->db->query("DELETE FROM bd_cded_cde_pda.coordinadores_db where Tiendas_Cod_Pos = '$cod_pos'");
		$this->db->query("DELETE FROM bd_cded_cde_pda.administradores where Tiendas_Cod_Pos = '$cod_pos'");
		$this->db->query("DELETE FROM bd_cded_cde_pda.horarios where Tiendas_Cod_Pos = '$cod_pos'");		
		$this->db->query("DELETE FROM bd_cded_cde_pda.tiendas where Cod_Pos = '$cod_pos'");

		$this->db->query("DELETE FROM gtr.cliente where COD_CDE = '$cod_pos'");
		$this->db->query("DELETE FROM gtr.infocde where CODPOS = '$cod_pos'");
 
	}
}

?>