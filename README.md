IFC to Json converter
=====================

## Installation / Usage

1. Download and install IFC2JSON via Composer

    ``` sh
    composer require system3d/ifc2json
    ```
    
2. Include the IFC2JSON class and set your IFC file

    ``` php
    use System3D\IFC2JSON\IFC2JSON;
    
    $ifcFile 	= "your-file.ifc";
	$IFC2JSON 	= new IFC2JSON( $ifcFile );
    ```

3. Get your JSON
	 ``` php
	 echo $IFC2JSON->getJson();
	 ```
	 

## Advanced mode

Advanced mode will return all the related objects intead of just an ref ID, and will filter the results to return only needed objects.

Pass TRUE as second argument to enable the Advanced mode

``` php
$IFC2JSON   = new IFC2JSON( $ifcFile, true );
```


## Levels density

By defaul, IFC2JSON will load in all the related objects for 3 levels only.

**1 level:**

![](https://raw.githubusercontent.com/system3d/ifc2json/master/assets/level1.png)

**2 levels:**

![](https://raw.githubusercontent.com/system3d/ifc2json/master/assets/level2.png)

**3 levels:**

![](https://raw.githubusercontent.com/system3d/ifc2json/master/assets/level3.png)


To get the related objects for bigger than 3 levels, pass an Integer as a third paramenter:
``` php
$IFC2JSON   = new IFC2JSON( $ifcFile, true, 5 );
```
BE CAREFUL! Higher levels of loading will results in a bigger outputs json file.