<html>
<head>
<title>Vitamin D - konverze XML exportu</title>
<link href="./css/style.css" type="text/css" rel="stylesheet" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>

<body>
<div class="page">
<div class="importni-formular">
<div class="formular-hlavicka"></div>
<div class="formular-obsah">
<form action="./prevod.php" method="post" enctype="multipart/form-data">
<table>
	<tr>
		<td>
			Typ exportu
		</td>
		<td>
			Dodaci list&nbsp;<input type="radio" name="typDokladu" value="dodak" />&nbsp;
			Dobropis&nbsp;<input type="radio" name="typDokladu" value="dobropis" />&nbsp;
		</td>
	</tr>
	<tr>
		<td>
			Číslo faktury
		</td>
		<td>
			<input type="text" name="cisloFaktury" size="30"/>
		</td>
	</tr>
	<tr>
		<td>
			Exportovaný XML soubor
		</td>
		<td>
			<input type="file" name="xmlSoubor" size="30"/>
		</td>
	</tr>
	<tr>
		<td>
			<input type="submit" value="Zpracovat" />
		</td>
	</tr>
</table>
</form>
</div>
<div class="formular-paticka"></div>
</div>
</div>
</body>

</html>