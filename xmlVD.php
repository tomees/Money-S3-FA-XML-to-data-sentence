<?php

/**
 * @author Tomas Pribyl
 *
 */
class xmlVD {
	
	private $xmlImport;				//importovany XML soubor
	private $datoveVety;			//promenna obsahujici jednotlive radky davky
	private $typDokladu;
	private $ico;					//ICO dodavatele
	private $fakturaCislo;			//cislo faktury
	private $dodaciListCislo;		//cislo dodaciho listu
	private $datum;					//datum dodani / expedice zbozi na prodejnu
	private $dekada = " ";			//cislo dekady - zatim nepouzivane
	private $cisloProdejny;			//cislo prodejny na ktreou bylo zbozi dodano
	private $registrZbozi;			//katalogove cislo zbozi
	private $nazevZbozi;			//nazev zbozi
	private $mernaJednotka;			//merna jednotka (ks, kg)
	private $mnozstvi;				//mnozstvi
	private $cenaZaJednotku;		//cena za jednotku
	private $dph;					//DPH
	private $celkovaCena;			//cena za jednotku * pocet kusu
	private $baleni = "0.000";		//baleni - nepouzite
	private $chyba = "";			//promenna do ktere se ukladaji chyby v prubehu zpracovani
	
	function __construct() {
		header("Content-Type: text/plain; charset: utf-8");
		$this->xmlImport = simplexml_load_file($_FILES["xmlSoubor"]["tmp_name"]);
		
	}
	
	public function setFakturaCislo($fakturaCislo) {
		//$this->fakturaCislo = $this->upravDelku($fakturaCislo, 10);
		$this->fakturaCislo = $fakturaCislo;
	}
	
	public function setTypDokladu($typDokladu) {
		$this->typDokladu = $typDokladu;
	} 
	
	public function zpracujDoklad() {
		
		switch ($this->typDokladu) {
			
			case "dodak":
				$this->parsujDodak();
				break;
			case "dobropis":
				$this->parsujDobropis();
				break;
		}
		
	}
	
	/**
	 * Privatni funkce slouzici k vyparsovani pozadovanych dat z exportu z
	 * dodacich listu (z Money S3)
	 */
	private function parsujDodak() {
		
		foreach ($this->xmlImport->SeznamDLVyd->DLVyd as $dodaciList) {
			
			//vytazeni cisla dodaciho listu z aktualne prochazeneho dodaku
			$this->dodaciListCislo = $this->upravDelku($dodaciList->CisloDokla,10);
			// vytazeni cisla ICO
			$this->ico = $dodaciList->MojeFirma->ICO;
			// ziskani a upraveni datumu z dodaku
			$this->datum = $this->upravDatum($dodaciList->DatSkPoh);
			$this->cisloProdejny = $this->upravDelku($dodaciList->Zakazka, 3);
			$this->vypisPolozkyDodaku($dodaciList);
			
			if($this->cisloProdejny == "000") {
				$this->chyba .= "<span style='color:red'>CHYBA:</span>U dodaciho listu ".$this->dodaciListCislo." neni vyplnena prodejna! (polozka ZAKAZKA)<br/>";
			}
			
		}
		
		if($this->chyba == "") {
			$this->stahnoutTXT();
		} else {
			echo $this->chyba;
		}
		
	}
	
	/**
	 * Privatni funkce slouzici k vyparsovani dat z exportu jedne faktury (Money S3)
	 *
	 */
	private function parsujDobropis() {
		
		$dobropis = $this->xmlImport->SeznamFaktVyd->FaktVyd;
		
		$this->fakturaCislo = $dobropis->Doklad;
		$this->ico = $dobropis->MojeFirma->ICO;
		$this->datum = $this->upravDatum($dobropis->PlnenoDPH);
		$this->dekada = " ";
		$this->dodaciListCislo = "0000000000";
		
		$this->vypisPolozkyDobropisu($dobropis);

		if($this->chyba == "") {
			$this->stahnoutTXT();
		} else {
			echo $this->chyba;
		}
	}

	private function vypisPolozkyDobropisu($dobropis) {
		foreach ($dobropis->SeznamPolozek->Polozka as $polozka) {
			
			$this->dodaciListCislo = $this->upravDelku($this->vytahniCisloDodaku($polozka->Popis), 10);
			$this->cisloProdejny = $this->upravDelku($polozka->NesklPolozka->Katalog, 3);
			$this->registrZbozi = $this->upravDelku("", 12);
			$this->nazevZbozi = $this->upravNazev("Dobropis");
			$this->mernaJednotka = $this->upravMJ(" ");
			$this->mnozstvi = $this->upravDelku($this->upravDesetinneCislo($polozka->PocetMJ, 2), 10);
			$this->cenaZaJednotku = $this->upravDelku($this->upravDesetinneCislo($polozka->Cena, 3), 13);
			$this->dph = $this->upravDPH($polozka->SazbaDPH);
			$this->celkovaCena = $this->upravDelku(number_format($this->mnozstvi * $this->cenaZaJednotku, 2), 12);
			$this->baleni = $this->upravDelku($this->baleni, 13);

            preg_match('/^obal ([0-9]+)$/', $polozka->Popis, $matches);

            if(is_numeric($matches[1])) {
                $this->nazevZbozi = $this->upravNazev($polozka->Popis);
                $this->registrZbozi = $this->upravDelku($matches[1], 12);
            }

            $this->vytvorDatovouVetu();

		}
	}
	
	private function vypisPolozkyDodaku($dodaciList) {

		
		foreach ($dodaciList->Polozka as $polozka) {
			
			$this->registrZbozi = $this->upravDelku(trim($polozka->KmKarta->Katalog), 12);
			$this->nazevZbozi = $this->upravNazev($polozka->Nazev);
			$this->mernaJednotka = $this->upravMJ($polozka->KmKarta->MJ);
			$this->mnozstvi = $this->upravDelku($this->upravDesetinneCislo($polozka->PocetMJ, 2), 10);
			$this->cenaZaJednotku = $this->upravDelku($this->upravDesetinneCislo($polozka->Cena, 3), 13);
			$this->dph = $this->upravDPH($polozka->DPH);
			$this->celkovaCena = $this->upravDelku(number_format($this->mnozstvi * $this->cenaZaJednotku, 2), 12);
			$this->baleni = $this->upravDelku($this->baleni, 13);
			$this->vytvorDatovouVetu();
					
		}
	}
	
	private function vytvorDatovouVetu() {
		
		$this->datoveVety .= $this->ico . $this->upravDelku($this->fakturaCislo, 10) . $this->dodaciListCislo . $this->dekada . $this->datum . $this->cisloProdejny
				. $this->registrZbozi . $this->nazevZbozi . $this->mernaJednotka
				. $this->mnozstvi . $this->cenaZaJednotku . $this->dph
				. $this->celkovaCena . $this->baleni . "\r\n";
	}
	
	private function stahnoutTXT() {
		
		header("Pragma: public"); // po�adov�no
    	header("Expires: 0");
    	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    	header("Cache-Control: private",false); // po�adov�no u n�kter�ch prohl�e��
    	header("Content-Transfer-Encoding: binary");
    	header("Content-Type: text/plain; charset: windows-1252");
    	header("Content-Disposition: attachment; filename=vitamind_".$this->fakturaCislo.".txt;" );
    // Fin�ln� odesl�n� souboru
    echo $this->datoveVety;
    die();
		
	}
	
	private function upravDatum($datum) {
		
		$upraveneDatum = str_replace("-","",$datum);
		
		return $upraveneDatum;
		
	}
	
	private function upravMJ($mj) {
		return str_pad($mj, 3, " ", STR_PAD_RIGHT);
	}
	
	private function upravDelku($retezec, $pocetZnaku) {
		
		$upravenyRetezec = str_pad($retezec, $pocetZnaku, " ", STR_PAD_LEFT);
		
		return $upravenyRetezec;
		
	}
	
	private function upravDesetinneCislo($cislo, $desetinnychCisel) {
		
		$rozdelene = explode(".", $cislo);
		$upravenneDesetine = str_pad($rozdelene[1], $desetinnychCisel, "0");
		
		return $rozdelene[0] . "." . $upravenneDesetine;
	}
	
	private function upravDPH($cislo) {
		if(strlen($cislo) < 2) {
			return "0" . $cislo . ".0";
		}
		else {
			return $cislo . ".0";
		}
	}
	
	private function nahradDiakritiku($text) {
		
		$return = strtr($text,

                    "áčďéěíľňóřšťúůýžÁČĎÉĚÍĽŇÓŘŠŤÚŮÝŽ",

                    "acdeeilnorstuuyzACDEEILNORSTUUYZ");

        //$return = Str_Replace(Array(" ", "_"), "-", $return); //nahradí mezery a podtržítka pomlčkami

        //$return = Str_Replace(Array("(",")",".","!",",","\"","'"), "", $return); //odstraní ().!,"'

        //$return = StrToLower($return); //velká písmena nahradí malými.

        return $return;

    }

	
	private function upravNazev($nazev) {
		
		setlocale(LC_ALL, 'czech');
		$orezNazev = iconv("UTF-8", "ASCII//TRANSLIT", $nazev);
		//$orezNazev = $this->nahradDiakritiku($orezNazev);
		//$orezNazev = $nazev;
		$orezNazev = str_replace('\'', '',$orezNazev);
		if(strlen($orezNazev) > 24) {
			$upravenyNazev = substr($orezNazev,0,24);
		} else {
			$upravenyNazev = str_pad($orezNazev,24," ");
		}
		return $upravenyNazev;
	}
	
	private function vytahniCisloDodaku($polozka) {
		
		preg_match('#[0-9\/]+#', $polozka, $cisloDodaku);

		return $cisloDodaku[0];
		
	}
}

