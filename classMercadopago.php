<?php
/**
* Clase para la integración del boton de pagos de mercadopago
* Desarrollado por Daniel Russian
* client_id es en identificador unico de cliente
* client_secret codigo secreto unico por cliente
*/
class classMercadopago 
{
	private $client_id = "COLOQUE EL SUYO AQUI";
	private $client_secret = "COLOQUE EL SUYO AQUI";

	private $access_token;
	private $url;

	public function __construct()
	{
		$this->access_token = $this->makeRequestToken();
	}

	/**
	* ============================== Autentícate ==============================
	* Obtener el acces token para interactuar con el API
	* La funcion devuelve el acces_token si se obtuvo con exito o NULL si fracaso la conexion
	* $url = es la direccion donde enviamos el json 
	* $post_data = los datos que enviamos al APi para permitir el token de acceso 
	*/

	private function makeRequestToken()
	{
		$appClientValues = "client_id=" . $this->client_id . "&client_secret=" . $this->client_secret . "&grant_type=client_credentials";
		$url = "https://api.mercadolibre.com/oauth/token";	                                                     
	    $handler = curl_init(); 
	    curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($handler, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'));
	    curl_setopt($handler, CURLOPT_URL, $url);           
	    curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);                  
	    curl_setopt($handler, CURLOPT_POSTFIELDS, $appClientValues);  
	    $response = curl_exec ($handler);  
	    
	    curl_close($handler);  
		$response = json_decode($response, true);		
		$this->access_token = $response['access_token'];
		return $this->access_token;		
	}

	/**
	* ============================== Configura la API de Checkout ==============================
	*
	* Creacion del arreglo con los datos del producto, mantiene formato estipulado por mercadopago
	* "items": [{
    *    "id": "Código",
    *    "title": "Nombre",
    *    "description": "Descripción",
    *    "quantity": 1,
    *    "unit_price": 50,
    *    "currency_id": "Tipo de moneda",// Argentina: ARS (peso argentino) ó USD (Dólar estadounidense); Brasil: BRL (Real).
    *    "picture_url": "https://www.mercadopago.com/org-img/MP3/home/logomp3.gif"
    * }],
	* @param $data 
	*/
	public function set_data_items( $data )
	{
		if( !isset( $data['quantity'] ) || !isset( $data['currency_id']) || !isset( $data['unit_price'] ) )
		{
			trigger_error("Hay escases de datos");
		}
		$itemsArray = array(
			'id' => $data['id'],
			'title' => $data['title'],
			'description' => $data['description'],
			'quantity' => $data['quantity'],
			'unit_price' => $data['unit_price'],
			'currency_id' => $data['currency_id'],
			'picture_url' => $data['picture_url'], 
			);  
		return $itemsArray;
	}

	/**
	* Datos del cliente que esta comprando el articulo 
	*
	* "payer": {
    *    "name": "user-name",
    *    "surname": "user-surname",
    *    "email": "user@mail.com"
    * },
    * @param $array : datos del pagador
	*/
	public function set_data_payer( $array )
	{
		$payerArray = array(
			'name' => $array['name'], 
			'surname' => $array['surname'],
			'email' => $array['email']
			);
		return $payerArray
	}

	/**
	* "back_urls": {
    *    "pending": "http://www.pending.com",
    *    "success": "https://www.success.com"
    * }
	* @param $array : urls de destino dependiendo del estado del pago
	*/
	public function set_data_backurls( $array )
	{
		$urlsArray = array(
			'pending' => $array['pending'], 
			'success' => $array['success'],
			);
		return $urlsArray
	}	

	/**
	* Con todos los datos tomados y listos preparo el checkout de pago
	* 
	* $items : arreglo con los datos de los items a pagar
	* $payer : información del comprador o persona que va a pagar
	* $back_urls : direcciones de redireccionamiento 
	*/
	public function config_checkout( $items, $payer, $back_urls )
	{
		$post_data['items'] = array( $items );
		$post_data['payer'] = $payer;
		$post_data['back_urls'] = $back_urls;    
	    $url = "https://api.mercadolibre.com/checkout/preferences?access_token=".$this->access_token;
	    $handler = curl_init();
	    curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($handler, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
	    curl_setopt($handler, CURLOPT_URL, $url);
	    curl_setopt($handler, CURLOPT_POSTFIELDS, json_encode($post_data));
	    $response = curl_exec($handler);
	    curl_close($handler);
	    $response = json_decode($response, true);
	    /*
	    * Se devuelve el id de la preferencia para poder consultar el estado de la operacion
	    * mediante un JSON 
	    */
	    return $response['id'];
	}

	/*GET a mercadopago para verificar estado de pago */
	function getback($id)
	{	
		$token = $this->access_token;
		/* realizar un GET a mercadopago*/
		$url = "https://api.mercadolibre.com/collections/notifications/$id?access_token=$token";
		$handler = curl_init();
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handler, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	  	curl_setopt($handler, CURLOPT_URL, $url);
		$response = curl_exec($handler);
		curl_close($handler);
		$response = json_decode($response, true);
		$dataArray = array();
		$dataArray['status'] = $response['collection']['status'];
		$dataArray['email'] = $response['collection']['payer']['email'];
		$dataArray['monto'] = $response['collection']['transaction_amount'];
		return $dataArray;
	}
}/*final de la clase*/
?>