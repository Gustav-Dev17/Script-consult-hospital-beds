<?php

date_default_timezone_set('America/Manaus');

require_once "/var/www/html/dashs/inc/config.php";
include_once "/var/www/html/dashs/inc/funcoes_basicas.php";

function unidades(){
    $query = '
    {
      query from elastic search
    }
    ';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://link",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => $query,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        CURLOPT_USERPWD => "user:password...",
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $retorno_json = curl_exec($curl);

    $retorno_json = str_replace('"3"', '"TRES"', $retorno_json); //replace number from the curl
    $retorno_json = str_replace('"7"', '"SETE"', $retorno_json);


    $resultado = json_decode($retorno_json); //turns the result content into a JSON file
    $nome_unidade = $resultado->aggregations->TRES->buckets;

    $und = "";

    foreach($nome_unidade as $key => $value) {

        $unidade = $value->key;

        foreach($value->SETE->buckets as $cidade) {

          $nomeCidade = $cidade->key;

          if($nomeCidade == 'Manaus'){
            $origin = 'CAPITAL'; 
          } else {
            $origin = 'INTERIOR';
          }
 
      }

        $qtd_todos = quantidade_cli_uti_unidade($unidade);

        $qtd_cli_cov = $qtd_todos['clinico_covid'];
        $qtd_cli_ncov = $qtd_todos['clinico_ncovid'];
        $qtd_uti_covid = $qtd_todos['uti_covid'];
        $qtd_uti_ncovid = $qtd_todos['uti_ncovid'];

        $cnpj_unidade = cnpj($unidade);

        $und = $und . '<br>' . $nomeCidade.' - '.$unidade.' - '.$cnpj_unidade.' - '.$qtd_cli_cov.' - '. $qtd_cli_ncov.' - '.$qtd_uti_covid.' - '. $qtd_uti_ncovid;

        // ATRIBUIÇÃO E INSERÇÃO DE VALORES UTI & CLI COVID

        $data_registro = date('Y-m-d');
        $hora_registro = date('H:i:s');
        $qt_transf_cli = $qtd_cli_cov;
        $qt_transf_uti = $qtd_uti_covid;
        $is_covid = 'S';
        $nome_und_saude = $unidade;
        $cnpj_unidade_saude = $cnpj_unidade;
        $orig_cap_int = $origin;

        $query = "INSERT INTO table_est_cli_uti VALUES (null, '$data_registro', '$hora_registro', $qt_transf_cli, $qt_transf_uti, '$is_covid', '$nome_und_saude', '$cnpj_unidade_saude', '$orig_cap_int')";
        $result = mysql_query($query) or die("Erro: " . mysql_error());
        $results = mysql_insert_id();

        // ATRIBUIÇÃO E INSERÇÃO DE VALORES UTI & CLI NÃO COVID

        $data_registro = date('Y-m-d');
        $hora_registro = date('H:i:s');
        $qt_transf_cli = $qtd_cli_ncov;
        $qt_transf_uti = $qtd_uti_ncovid;
        $is_covid = 'N';
        $nome_und_saude = $unidade;
        $cnpj_unidade_saude = $cnpj_unidade;
        $orig_cap_int = $origin;

        $query = "INSERT INTO table_est_cli_uti VALUES (null, '$data_registro', '$hora_registro', $qt_transf_cli, $qt_transf_uti, '$is_covid', '$nome_und_saude', '$cnpj_unidade_saude', '$orig_cap_int')";
        $result = mysql_query($query) or die("Erro: " . mysql_error());
        $results = mysql_insert_id();

    }

    return $und;
   
}

function quantidade_cli_uti_unidade($nome_unidade){
    $query = '
        {        
          query elastic search
        }
      ';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://link",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => $query,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        CURLOPT_USERPWD => "user:password",
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $retorno_json = curl_exec($curl);
    $retorno_json = str_replace('"2"', '"DOIS"', $retorno_json);
    $retorno_json = str_replace('"6"', '"SEIS"', $retorno_json);
    $retorno_json = str_replace('"3"', '"TRES"', $retorno_json);
    $retorno_json = str_replace('"CLÍNICO"', '"CLINICO"', $retorno_json);
    $retorno_json = str_replace('"Não COVID"', '"NAO_COVID"', $retorno_json);

    $resultado = json_decode($retorno_json);
    $quantitativo = $resultado->aggregations->TRES->buckets;

    foreach($quantitativo as $q) {
        $quant_cli_cov = $q->DOIS->buckets->CLINICO->SEIS->buckets->COVID->doc_count;
        $quant_cli_ncov = $q->DOIS->buckets->CLINICO->SEIS->buckets->NAO_COVID->doc_count;
        $quant_uti_cov = $q->DOIS->buckets->UTI->SEIS->buckets->COVID->doc_count;
        $quant_uti_ncov = $q->DOIS->buckets->UTI->SEIS->buckets->NAO_COVID->doc_count;

        $quant_unid_leitos = array(
            'clinico_covid' => $quant_cli_cov,
            'clinico_ncovid' => $quant_cli_ncov,
            'uti_covid' => $quant_uti_cov,
            'uti_ncovid' => $quant_uti_ncov
        );
    }

    return $quant_unid_leitos;
    // return $retorno_json;
}

function cnpj($nome_unidade){

  array(
    $cnpj_und['SPA e Policlinica Dr. Danilo Corrêa'] = '00697295009587',
    $cnpj_und['Hospital Geral de Manacapuru'] = '04274064000131',
    $cnpj_und['UPA 24h José Rodrigues'] = '00697295011727',
    $cnpj_und['SPA Alvorada'] = '00697295009315',
    $cnpj_und['H.P.S. 28 de Agosto'] = '00697295006138',
    $cnpj_und['Hospital Geral Jose Mendes'] = '00697295005670',
    $cnpj_und['Hospital Regional de Tefé'] = '00697295000954',
    $cnpj_und['SPA Coroado'] = '00697295009404',
    $cnpj_und['SPA Enfermeira Eliameme Rodrigues Mady'] = '00697295009749',
    $cnpj_und['SPA São Raimundo'] = '00697295009234',
    $cnpj_und['UPA 24h Campos Sales'] = '00697295011808',
    $cnpj_und['Unidade Hospitalar de Autazes'] = '41752597249',
    $cnpj_und['Hospital Dr. Luiza da Conceição Fernandes'] = '04465209000181',
    $cnpj_und['Hospital Geral Eraldo Neves Falcão'] = '12443566007788',
    $cnpj_und['Hospital de Borba Vó Mundoca'] = '21332123001122',
    $cnpj_und['Hospital de Maués'] = '00697295005328',
    $cnpj_und['SPA Joventina Dias'] = '00697295009668',
    $cnpj_und['SPA e Policlinica Dr. José de Jesus Lins de Albuquerque'] = '00697295009153',
    $cnpj_und['Unidade Hospitalar de São Sebastião do Uatumã'] = '00697295004437',
    $cnpj_und['Fundação de Medicina Tropical'] = '04534053000143',
    $cnpj_und['H.P.S. Dr. Aristóteles Platão B. de Araújo'] = '00697295009072',
    $cnpj_und['H.P.S.C. Zona Oeste'] = '00697295007703',
    $cnpj_und['Hospital Regional Dr. Jofre de Matos Cohen'] = '04329736000169',
    $cnpj_und['Hospital Regional de Coari'] = '04262432000121',
    $cnpj_und['Hospital Universitário Francisca Mendes'] = '21245455000147',
    $cnpj_und['Hospital de Guarnição de São Gabriel da Cachoeira'] = '06333890009999',
    $cnpj_und['Maternidade Alvorada'] = '00697295001179',
    $cnpj_und['Maternidade Dona Nazira Daou'] = '00697295001764',
    $cnpj_und['Maternidade Municipal Dr. Moura Tapajoz'] = '—',
    $cnpj_und['SPA e Maternidade Chapot Prevost'] = '00697295000792',
    $cnpj_und['Unidade Hospitalar de Alvarães'] = '00697295006804',
    $cnpj_und['Unidade Hospitalar de Anori'] = '00697295003708',
    $cnpj_und['Unidade Hospitalar de Atalaia do Norte'] = '00697295003201',
    $cnpj_und['Unidade Hospitalar de Barreirinha'] = '00697295804194',
    $cnpj_und['Unidade Hospitalar de Beruri'] = '04628111000106',
    $cnpj_und['Unidade Hospitalar de Carauari'] = '00697295002736',
    $cnpj_und['Unidade Hospitalar de Codajás'] = '00697295002906',
    $cnpj_und['Unidade Hospitalar de Eirunepé'] = '00697295001500',
    $cnpj_und['Unidade Hospitalar de Iranduba'] = '66987440263',
    $cnpj_und['Unidade Hospitalar de Itapiranga'] = '00697295001411',
    $cnpj_und['Unidade Hospitalar de Japurá'] = '00697295006480',
    $cnpj_und['Unidade Hospitalar de Lábrea'] = '00697295002493',
    $cnpj_und['Unidade Hospitalar de Manicoré'] = '00697295003112',
    $cnpj_und['Unidade Hospitalar do Castanho'] = '83828265200',
  );
  
  return $cnpj_und[$nome_unidade];
}

echo "<br><br>The script has been successfully run!<br><br>";