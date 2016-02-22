<?php

namespace Muserpol\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Session;
use Validator;
use Muserpol\Http\Requests;
use Muserpol\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Muserpol\Afiliado;
use Muserpol\Aporte;
use Muserpol\Helper\Util;

$countAfi = 0;
$countApor = 0;

class ImportController extends Controller
{

    public function import(Request $request)
    {
    	global $countAfi, $countApor;

		ini_set('upload_max_filesize', '700M');
		ini_set('post_max_size', '700M');
		ini_set('max_execution_time', 36000);
		ini_set('max_input_time', 36000);
		ini_set("memory_limit","700M");
    	set_time_limit(36000);

    	$reader = $request->file('archive');
        $filename = $reader->getRealPath();

        Excel::selectSheetsByIndex(0)->load($filename, function($reader) {

			$count = 0;
			$col = array('car', 'pat', 'mat', 'nom', 'nom2', 'apes', 'eciv', 'sex', 'nac', 'ing', 'mes', 'a_o', 'uni', 'desg', 
						'niv', 'gra', 'item', 'sue', 'cat', 'est', 'carg', 'fro', 'ori', 'bseg', 'dfu', 'nat', 'lac', 'pre', 'sub', 'gan', 'mus');

		 	$results = $reader->select($col)->first();
			 
			foreach ($results as $nombre => $valor) {
				if (in_array($nombre, $col)) {
					$count ++;
				}
			}	

			if ($count < count($col))
			{
				$message = "Falta Columnas, favor Verificar el Archivo";
				Session::flash('message', $message);
				return view('import.import_select');
				break;
			}
		});

		$col = array('car', 'pat', 'mat', 'nom', 'nom2', 'apes', 'eciv', 'sex', 'nac', 'ing', 'mes', 'a_o', 'uni', 'desg', 
							'niv', 'gra', 'item', 'sue', 'cat', 'est', 'carg', 'fro', 'ori', 'bseg', 'dfu', 'nat', 'lac', 'pre', 'sub', 'gan', 'mus');

     	Excel::selectSheetsByIndex(0)->filter('chunk')->select($col)->load($filename,$reader)->chunk(500, function($results) {

	        $rules = [
	            'car' => 'required',
	            'pat' => 'alpha',
	            'mat' => 'alpha',
	            'nom,' => 'alpha',
	            'nom2' => 'alpha',
	            'apes' => 'alpha',
	            'eciv' => 'alpha|in:S,C',
	            'sex' => 'alpha|in:M,F',
	            'nac' => 'date',
	            'ing' => 'date',
	            'mes' => 'numeric|required',
	            'a_o' => 'numeric|required',
	            'uni' => 'numeric',
	            'desg' => 'numeric',
	            'niv' => 'numeric|required',
	            'gra' => 'numeric|required',
	            'item' => 'numeric|required',
	            'sue' => 'numeric',
	            'cat' => 'numeric',
	            'est' => 'numeric',
	            'carg' => 'numeric',
	            'fro' => 'numeric',
	            'ori' => 'numeric',
	            'bseg' => 'numeric',
	            'dfu' => 'numeric',
	            'nat' => 'numeric',
	            'lac' => 'numeric',
	            'pre' => 'numeric',
	            'sub' => 'numeric',
	            'gan' => 'numeric',
	            'mus' => 'numeric',
	        ];

	        $messages = [
				'car.required' => 'El campo CAR - Número de Carnet se encuentra vacío',
				'pat.alpha' => 'El campo PAT - Apellido Paterno tiene que ser solo letras',
				'mat.alpha' => 'El campo MAT - Apellido Paterno tiene que ser solo letras',
				//add messages
			];

	   		foreach ($results as $result) {
	   			
	   			set_time_limit(36000);

	   			$result->car = Util::zero($result->car);
	        	$result->nac = Util::date($result->nac);

	        	$result->mes = Util::zero($result->mes);
	        	$result->a_o = Util::zero($result->a_o);

				$validator = Validator::make((array)$result, $rules, $messages);

		        if ($validator->fails()){
		            return redirect('import.import_select')
		            ->withErrors($validator)
		            ->withInput();
		        }

	   		}
	   	});

     	Excel::selectSheetsByIndex(0)->filter('chunk')->select($col)->load($filename,$reader)->chunk(500, function($results) {

     		global $countAfi, $countApor;

     		$col = array('car', 'pat', 'mat', 'nom', 'nom2', 'apes', 'eciv', 'sex', 'nac', 'ing', 'mes', 'a_o', 'uni', 'desg', 
						'niv', 'gra', 'item', 'sue', 'cat', 'est', 'carg', 'fro', 'ori', 'bseg', 'dfu', 'nat', 'lac', 'pre', 'sub', 'gan', 'mus');

			foreach ($results as $result) {
				
				set_time_limit(36000);

				$carnet = Util::zero($result->car);

				$afiliado = Afiliado::where('ci', '=', $carnet)->first();
				
				if ($afiliado === null) {
	
					$afiliado = new Afiliado;
	        		$afiliado->ci = $carnet;
	        		$afiliado->pat = $result->pat;
	        		$afiliado->mat = $result->mat;
	        		$afiliado->nom = $result->nom;
	        		$afiliado->nom2 = $result->nom2;
	        		$afiliado->ap_esp = $result->apes;
	        		$afiliado->est_civ = $result->eciv;
	        		$afiliado->sex = $result->sex;
	        		$afiliado->matri = Util::calcMatri($result->nac, $afiliado->pat, $afiliado->mat, $afiliado->nom, $afiliado->sex);
	        		$afiliado->fech_nac = Util::date($result->nac);
	        		$afiliado->fech_ing = Util::date($result->ing);
	       	 		$afiliado->save();
	       	 		$countAfi ++;
				}

				$aporte = Aporte::where('mes', '=', $result->mes)
								->where('anio', '=', $result->a_o)
								->where('afiliado_id', '=', $afiliado->id)->first();

				if ($aporte === null) {

					$aporte = new Aporte;
					$aporte->afiliado_id = $afiliado->id;
					$aporte->mes = $result->mes;
					$aporte->anio = $result->a_o;
					$aporte->uni = $result->uni;
					$aporte->desg = $result->desg;
					$aporte->niv = $result->niv;
					$aporte->gra = $result->gra;
					$aporte->item = $result->item;

					$aporte->sue = Util::decimal($result->sue);
					$aporte->b_ant = Util::decimal($result->cat);
					$aporte->cat = Util::calcCat($aporte->b_ant,$aporte->sue);
					$aporte->b_est = Util::decimal($result->est);
					$aporte->b_car = Util::decimal($result->carg);
					$aporte->b_fro = Util::decimal($result->fro);
					$aporte->b_ori = Util::decimal($result->ori);
					$aporte->b_seg = Util::decimal($result->bseg);
					$aporte->dfu = $result->dfu;
					$aporte->nat = $result->nat;
					$aporte->lac = $result->lac;
					$aporte->pre = $result->pre;
					$aporte->sub = Util::decimal($result->sub);
					$aporte->gan = Util::decimal($result->gan);
					$aporte->cot = (FLOAT)$aporte->sue + (FLOAT)$aporte->b_ant + (FLOAT)$aporte->b_est + (FLOAT)$aporte->b_car + (FLOAT)$aporte->b_fro + (FLOAT)$aporte->b_ori;
					// $aporte->cot_adi = ;
					$aporte->mus = Util::decimal($result->mus);
	     			$aporte->save();
	     			$countApor ++;
	     		}
      		}

		});

      	$message = "Se realizaron " . $countAfi . " registros de Afiliados Nuevos y " . $countApor . " en Planillas";

        Session::flash('message', $message);

		return view('import.import_select');
    }

    public function importSelect()
    {
		return view('import.import_select');
    }
}
