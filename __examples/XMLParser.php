<?php
/*
include '../LibBase.php';
include '../util/uParser.php';
include '../util/uLexer.php';
include '../util/XMLParser/XMLParser.php';


$parser = new XMLParser();


$parser->parse(
<<<PPP
xml version="1.0" encoding="UTF-8"?>
<Comprobante xmlns="http://www.sat.gob.mx/cfd/2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv2.xsd" version="2.0" serie="A" folio="578" fecha="2011-08-31T12:18:03" sello="" noAprobacion="281088" anoAprobacion="2010" formaDePago="PAGO EN UNA SOLA EXHIBICION" noCertificado="" certificado="MIIEIjCCAwqgAwIBAgIUMDAwMDEwMDAwMDAxMDIzMzYwNTEwDQYJKoZIhvcNAQEFBQAwggE2MTgwNgYDVQQDDC9BLkMuIGRlbCBTZXJ2aWNpbyBkZSBBZG1pbmlzdHJhY2nDs24gVHJpYnV0YXJpYTEvMC0GA1UECgwmU2VydmljaW8gZGUgQWRtaW5pc3RyYWNpw7NuIFRyaWJ1dGFyaWExHzAdBgkqhkiG9w0BCQEWEGFjb2RzQHNhdC5nb2IubXgxJjAkBgNVBAkMHUF2LiBIaWRhbGdvIDc3LCBDb2wuIEd1ZXJyZXJvMQ4wDAYDVQQRDAUwNjMwMDELMAkGA1UEBhMCTVgxGTAXBgNVBAgMEERpc3RyaXRvIEZlZGVyYWwxEzARBgNVBAcMCkN1YXVodGVtb2MxMzAxBgkqhkiG9w0BCQIMJFJlc3BvbnNhYmxlOiBGZXJuYW5kbyBNYXJ0w61uZXogQ29zczAeFw0xMDEyMDkwMDAwNTFaFw0xMjEyMDgwMDAwNTFaMIHCMR4wHAYDVQQDExVDT01QVVRFUiBTWVMgU0EgREUgQ1YxHjAcBgNVBCkTFUNPTVBVVEVSIFNZUyBTQSBERSBDVjEeMBwGA1UEChMVQ09NUFVURVIgU1lTIFNBIERFIENWMSUwIwYDVQQtExxDU1kwNDAzMTk1TjYgLyBDQUdYNjgwNDE0N0IzMR4wHAYDVQQFExUgLyBDQUdYNjgwNDE0SERGUk1MMDIxGTAXBgNVBAsTEENPTVBVVEVSIFBPTEFOQ08wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMtP6WPOc9M3hrZLhurSwM3Gbb+VuI9QnY3HmRXJrSwYEyte9QqtG4MniL8+hHo+1t1igrYcOimSd1AdQJXmPiYcEbCUYgEmsJM43YtTlEgrueVtkzFN6qMqUT5q1pjrqSOTPIcyPdxJX6/EqBHPf8x75tvWyhpAdDflhaguadHVAgMBAAGjHTAbMAwGA1UdEwEB/wQCMAAwCwYDVR0PBAQDAgbAMA0GCSqGSIb3DQEBBQUAA4IBAQCDABOTY+EsH/KkgJjszBiD+uHSTYiGnWOEKf5U0S7FJsvNlwetEhBfWZfjzK+H6hF14gvkOJtSAZegRyQaFj2AV6B8hisce3Phux2Noix7XjVE8KufTKFaNIUT+tGaGhT3+eO8Zmtr/v1KGos5fgQq9f5nvhFsPUIFX2B1ypMrnHF12IVOMysJvtiiq+orsu3wrFgGvYnnWBIH+Cn6+0kn+kQotngZh2KsxchHBcEDu1X9xd+21hGAvZuZAPr7GOrNASJb7h/GmStMo9zuD7XLngKuO0NnV1cbCD0AvcrW6/nEYuhINp7ug7oYgb6B3Cx6BcHx9hT64yTklDdzwEi4" subTotal="1000.00" total="1160.00" tipoDeComprobante="ingreso">
	<Emisor rfc=" CSY0403195N6" nombre="COMPUTER SYS, S.A. DE C.V.">
		<DomicilioFiscal calle="LOPE DE VEGA" noExterior="239" colonia="CHAPULTEPEC MORALES" localidad="MEXICO" municipio="MIGUEL HIDALGO" estado="DISTRITO FEDERAL" pais="MEXICO" codigoPostal="11570"/></Emisor>
	<Receptor rfc=" PDG031217IA7" nombre="Publicidad Delta Group, S.C.">
		<Domicilio calle="Paseo de la Reforma" noExterior="985" colonia="Lomas de Chapultepec" localidad="México" municipio="Miguel Hidalgo" estado="Distrito Federal" pais="México" codigoPostal="11000"/></Receptor>
	<Conceptos>
		<Concepto cantidad="2" descripcion="SVC CABLE ASSY MINI DP TO VGA" valorUnitario="500.00" importe="1000.00"/></Conceptos>
	<Impuestos totalImpuestosTrasladados="160.00">
		<Traslados><Traslado impuesto="IVA" tasa="16.00" importe="160.00"/></Traslados></Impuestos>
</Comprobante>

PPP
);

*/

$xml = DOMDocument::loadXML(
    utf8_encode(
<<<PPP
<?xml version="1.0" encoding="UTF-8"?>
<Comprobante xmlns="http://www.sat.gob.mx/cfd/2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv2.xsd" version="2.0" serie="A" folio="578" fecha="2011-08-31T12:18:03" sello="" noAprobacion="281088" anoAprobacion="2010" formaDePago="PAGO EN UNA SOLA EXHIBICION" noCertificado="" certificado="MIIEIjCCAwqgAwIBAgIUMDAwMDEwMDAwMDAxMDIzMzYwNTEwDQYJKoZIhvcNAQEFBQAwggE2MTgwNgYDVQQDDC9BLkMuIGRlbCBTZXJ2aWNpbyBkZSBBZG1pbmlzdHJhY2nDs24gVHJpYnV0YXJpYTEvMC0GA1UECgwmU2VydmljaW8gZGUgQWRtaW5pc3RyYWNpw7NuIFRyaWJ1dGFyaWExHzAdBgkqhkiG9w0BCQEWEGFjb2RzQHNhdC5nb2IubXgxJjAkBgNVBAkMHUF2LiBIaWRhbGdvIDc3LCBDb2wuIEd1ZXJyZXJvMQ4wDAYDVQQRDAUwNjMwMDELMAkGA1UEBhMCTVgxGTAXBgNVBAgMEERpc3RyaXRvIEZlZGVyYWwxEzARBgNVBAcMCkN1YXVodGVtb2MxMzAxBgkqhkiG9w0BCQIMJFJlc3BvbnNhYmxlOiBGZXJuYW5kbyBNYXJ0w61uZXogQ29zczAeFw0xMDEyMDkwMDAwNTFaFw0xMjEyMDgwMDAwNTFaMIHCMR4wHAYDVQQDExVDT01QVVRFUiBTWVMgU0EgREUgQ1YxHjAcBgNVBCkTFUNPTVBVVEVSIFNZUyBTQSBERSBDVjEeMBwGA1UEChMVQ09NUFVURVIgU1lTIFNBIERFIENWMSUwIwYDVQQtExxDU1kwNDAzMTk1TjYgLyBDQUdYNjgwNDE0N0IzMR4wHAYDVQQFExUgLyBDQUdYNjgwNDE0SERGUk1MMDIxGTAXBgNVBAsTEENPTVBVVEVSIFBPTEFOQ08wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMtP6WPOc9M3hrZLhurSwM3Gbb+VuI9QnY3HmRXJrSwYEyte9QqtG4MniL8+hHo+1t1igrYcOimSd1AdQJXmPiYcEbCUYgEmsJM43YtTlEgrueVtkzFN6qMqUT5q1pjrqSOTPIcyPdxJX6/EqBHPf8x75tvWyhpAdDflhaguadHVAgMBAAGjHTAbMAwGA1UdEwEB/wQCMAAwCwYDVR0PBAQDAgbAMA0GCSqGSIb3DQEBBQUAA4IBAQCDABOTY+EsH/KkgJjszBiD+uHSTYiGnWOEKf5U0S7FJsvNlwetEhBfWZfjzK+H6hF14gvkOJtSAZegRyQaFj2AV6B8hisce3Phux2Noix7XjVE8KufTKFaNIUT+tGaGhT3+eO8Zmtr/v1KGos5fgQq9f5nvhFsPUIFX2B1ypMrnHF12IVOMysJvtiiq+orsu3wrFgGvYnnWBIH+Cn6+0kn+kQotngZh2KsxchHBcEDu1X9xd+21hGAvZuZAPr7GOrNASJb7h/GmStMo9zuD7XLngKuO0NnV1cbCD0AvcrW6/nEYuhINp7ug7oYgb6B3Cx6BcHx9hT64yTklDdzwEi4" subTotal="1000.00" total="1160.00" tipoDeComprobante="ingreso">
	<Emisor rfc=" CSY0403195N6" nombre="COMPUTER SYS, S.A. DE C.V.">
		<DomicilioFiscal calle="LOPE DE VEGA" noExterior="239" colonia="CHAPULTEPEC MORALES" localidad="MEXICO" municipio="MIGUEL HIDALGO" estado="DISTRITO FEDERAL" pais="MEXICO" codigoPostal="11570"/></Emisor>
	<Receptor rfc=" PDG031217IA7" nombre="Publicidad Delta Group, S.C.">
		<Domicilio calle="Paseo de la Reforma" noExterior="985" colonia="Lomas de Chapultepec" localidad="México" municipio="Miguel Hidalgo" estado="Distrito Federal" pais="México" codigoPostal="11000"/></Receptor>
	<Conceptos>
		<Concepto cantidad="2" descripcion="SVC CABLE ASSY MINI DP TO VGA" valorUnitario="500.00" importe="1000.00"/></Conceptos>
	<Impuestos totalImpuestosTrasladados="160.00">
		<Traslados><Traslado impuesto="IVA" tasa="16.00" importe="160.00"/></Traslados></Impuestos>
</Comprobante>
PPP

)
        );


$node = $xml->getElementsByTagName('Comprobante');

var_dump($node->item(0)->getAttribute('fecha'));

