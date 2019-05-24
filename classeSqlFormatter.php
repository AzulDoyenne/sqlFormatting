<?php
class SqlFormatter {
	private /*String*/ $selectIni;
	private /*String*/ $selectFin;
	private /*array*/  $subquerys;
	
	function __construct($query) {
		// infos da query externa
		$this->selectIni = substr($query,0,strpos($query,' FROM (')+7);
		$this->selectFin = substr($query,strpos($query,') tabelas '));
		
		// separar subquerys internas
		$subquerys = str_replace($this->selectFin,'',str_replace($this->selectIni,'',$query));
		$this->subquerys = explode(' UNION ALL ', $subquerys);
	}
	
	static function formatar($query) {
		$sql = new SqlFormatter($query);
		$tpl = new Template();
		$tpl->setFolder('debugQuery');
		
		$tpl->getHtmlBefore('table');
		
		$sql->formatarCabecalho($tpl,$sql->selectIni);
		
		// subquerys
		$conta = 0;
		foreach($sql->subquerys as $subquery) {
			$conta++;
			if($conta > 1) {
				$tpl->getHtmlBefore('tr');
				$tpl->getHtml('td','');
				$tpl->getHtml('td-colspan2','UNION ALL');
				$tpl->getHtmlAfter('tr');
			}
			
			$tpl->getHtmlBefore('tr');
			$tpl->getHtml('td','');
			$tpl->getHtmlBefore('td-colspan2');
			$tpl->getHtmlBefore('table');
			
			$selectIni = substr($subquery,0,strpos($subquery,' FROM ')+6);
			$sql->formatarCabecalho($tpl, $selectIni);
			$partes = explode(' WHERE ',str_replace($selectIni,'',$subquery));
			$from  = $partes[0];
			$where = count($partes) === 2 ? $partes[1] : '';
			
			$tpl->getHtmlBefore('tr');
			$tpl->getHtml('td','');
			$tpl->getHtml('td',$from);
			$tpl->getHtmlAfter('tr');
			
			$wheres = explode(' AND ',$where);
			$tpl->getHtmlBefore('tr');
			$tpl->getHtml('td','WHERE');
			$tpl->getHtml('td',array_shift($wheres));
			$tpl->getHtmlAfter('tr');
			foreach($wheres as $and) {
				$tpl->getHtmlBefore('tr');
				$tpl->getHtml('td','AND');
				$tpl->getHtml('td',$and);
				$tpl->getHtmlAfter('tr');
			}
			
			$tpl->getHtmlAfter('table');
			$tpl->getHtmlAfter('td-colspan2');
			$tpl->getHtmlAfter('tr');
		}
		
		$tpl->getHtmlBefore('tr');
		$tpl->getHtml('td-colspan3',$sql->selectFin);
		$tpl->getHtmlAfter('tr');
		
		$tpl->getHtmlAfter('table');
		return $tpl->printHtml();
	}
	
	private function formatarCabecalho($tpl,$cabecalho) {
		// primeira linha
		$tpl->getHtmlBefore('tr');
		$tpl->getHtml('td','SELECT&nbsp;');
		
		// colunas de exibição
		$conta = 0;
		$from = strpos($cabecalho,' FROM (') ? '(' : '';
		$colunas = str_replace(" FROM $from",'',str_replace('SELECT ','',$cabecalho));
		$total = explode('", ',$colunas);
		foreach($total as $coluna) {
			$conta++;
			// na primeira linha a primeira coluna vem ao lado do SELECT
			if($conta > 1) {
				$tpl->getHtmlBefore('tr');
				$tpl->getHtml('td','');
			}
			
			list($dados,$alias) = explode(' as ',$coluna.''.($conta < count($total) ? '",' : ''));
			$dadosTratados = $dados;
			// formatar cases
			if(substr($dados,0,5) === 'CASE ') {
				$tplCASE = new Template();
				$tplCASE->setFolder('debugQuery');
				
				$tplCASE->getHtmlBefore('table');
				$tplCASE->getHtmlBefore('tr');
				$tplCASE->getHtml('td','CASE&nbsp;');
				$contaCASE = 0;
				foreach(explode('CASE ',substr($dados,5)) as $dado) {
					$contaCASE++;
					if($contaCASE > 1) {
						$tplCASE->getHtmlBefore('tr');
						$tplCASE->getHtml('td','');
						$tplCASE->getHtml('td','CASE&nbsp;');
					}
					
					$tplCASE->getHtmlBefore('td'.($contaCASE === 1 ? '-colspan2' : ''));
					$tplCASE->getHtmlBefore('table');
					foreach(explode('WHEN ',substr($dado,5)) as $case) {
						list($when,$then) = explode('THEN ',$case);
						$tplCASE->getHtmlBefore('tr');
						$tplCASE->getHtml('td','WHEN&nbsp;');
						$tplCASE->getHtml('td',$when);
						$tplCASE->getHtmlAfter('tr');
						$tplCASE->getHtmlBefore('tr');
						$tplCASE->getHtml('td','THEN&nbsp;');
						$tplCASE->getHtml('td',$then);
						$tplCASE->getHtmlAfter('tr');
					}	
					$tplCASE->getHtmlAfter('table');
					$tplCASE->getHtmlAfter('td'.($contaCASE === 1 ? '-colspan2' : ''));
					$tplCASE->getHtmlAfter('tr');
					
				}
				$tplCASE->getHtmlAfter('tr');
				$tplCASE->getHtmlAfter('table');
				$dadosTratados = $tplCASE->printHtml();
			}
			$tpl->getHtml('td',"$dadosTratados");
			$tpl->getHtml('td',"as $alias");
			
			$tpl->getHtmlAfter('tr');
		}
		
		// from para subquerys
		$tpl->getHtmlBefore('tr');
		$tpl->getHtml('td'," FROM $from");
		$tpl->getHtml('td','');
		$tpl->getHtml('td','');
		$tpl->getHtmlAfter('tr');
		
		return $tpl;
	}
}