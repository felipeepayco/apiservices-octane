  

# Servicio SMS 
Proveedor de envió de SMS
## Uso
Usando el Facade `Sms`
```php
use App\Libs\Sms\Facade\Sms;

class ServicesController extends Controller 
{
	public function sendMessage()
	{
		Sms::to('573103001111')
			->message('Esto es un mensaje')
			->send();
	}
}
```
Se puede configurar un nuevo proveedor de SMS,
```php
use App\Libs\Sms\Facade\Sms;

class ServicesController extends Controller 
{
	public function sendMessage()
	{
		Sms::provider('movistar')
			->to('573103001111')
			->message('Esto es un mensaje')
			->send();
	}
}
```

Adicionalmente, se le puede pasar parámetros personalizados al proveedor a traves de la función options() que recibe un array como parámetro
```php
use App\Libs\Sms\Facade\Sms;

class ServicesController extends Controller 
{
	public function sendMessage()
	{
		Sms::provider('movistar')
			->to('573103001111')
			->message('Esto es un mensaje')
			->options([])
			->send();
	}
}
```

## Agregar un Nuevo Proveedor
si se requiere agregar un nuevo proveedor de envió de SMS, se debe agregar el driver, en la ruta `app/Libs/Sms/Drivers` y extenderlo de la clase `Driver`:

```php
use App\Libs\Sms\Facade\Sms;

class  MyProviderDriver  extends  Driver
{

	public  function  options($options = [])
	{
		$this->options = array_merge(
			$this->options,
			$options
		);
		return  $this;
	}

	public  function  send()
	{
		// implement a custom client	
	}
}
```
