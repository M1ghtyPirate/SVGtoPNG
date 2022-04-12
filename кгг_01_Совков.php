<?php
	// Ширина и высота изображения, минимальный отступ от краев
	$W=640;
	$H=480;
	$M=10;

	//header ("Content-type: image/png");
	$im = imagecreate($W, $H);
	// Цвет фона
	$bg = imagecolorallocate($im, 80, 100, 80);
	
	// Чтения файла
	$d=file_get_contents("Moose.svg");
	
	$d=preg_split('/(<style.*>|<\/style>)/', $d);
	$styles=str_replace("\n", "", $d[1]);
	
	$polygons_temp=preg_split('/(<g.*>|<\/g>)/', $d[2]);
	
	$j=0;
	$polygons=Array();
	for ($i=0;$i<count($polygons_temp);$i++) {
		$polygons_temp[$i]=str_replace("\n", "", $polygons_temp[$i]);	// !!!
		if (preg_match('/<polygon(.*?)\/>/', $polygons_temp[$i])) {
			$polygons[$j]=$polygons_temp[$i];
			$j++;
		}
	}
	
	
	for ($i=0;$i<count($polygons);$i++) {
		$j=0;
		preg_match('/class="(.*?)"/', $polygons[$i], $styles_pointer);
		preg_match("/$styles_pointer[1]{fill:#(.*?);}/", $styles, $pcolor_temp);
		$pcolor[$i][0]=hexdec(substr($pcolor_temp[1], 0, 2));
		$pcolor[$i][1]=hexdec(substr($pcolor_temp[1], 2, 2));
		$pcolor[$i][2]=hexdec(substr($pcolor_temp[1], 4, 2));
		
		preg_match('/points="(.*?)"/', $polygons[$i], $points_temp_arr);
		$points_temp_arr[1]=" ".$points_temp_arr[1];
		$offset=0;
		while (preg_match('/\s(.*?),(.*?)\s/', $points_temp_arr[1], $points_temp, PREG_OFFSET_CAPTURE, $offset)) {
			$offset=$points_temp[2][1]+strlen($points_temp[2][0]);
			$points[$i][$j]=$points_temp[1][0];
			$points[$i][$j+1]=$points_temp[2][0];
			$j+=2;
		}
	}
	
	$maxw=$maxh=0;
	$minw=$points[0][0];
	$minh=$points[0][1];
	for ($i=0;$i<count($points);$i++) {
		for ($j=0;$j<count($points[$i]);$j+=2) {
			if ($maxw<$points[$i][$j]) $maxw=$points[$i][$j];
			if ($maxh<$points[$i][$j+1]) $maxh=$points[$i][$j+1];
			if ($minw>$points[$i][$j]) $minw=$points[$i][$j];
			if ($minh>$points[$i][$j+1]) $minh=$points[$i][$j+1];
		}
	}
	$maxw-=$minw;
	$maxh-=$minh;
	
	// Нормализуем значения координат.
	for ($i=0;$i<count($points);$i++) {
		for ($j=0;$j<count($points[$i]);$j+=2) {
			$points[$i][$j]=($points[$i][$j]-$minw)/$maxw;
			$points[$i][$j+1]=($points[$i][$j+1]-$minh)/$maxh;
		}
	}
	
	//Пропорции сохраняются
	if ($maxw/($W-2*$M)>$maxh/($H-2*$M)) {
		$maxneww=$W-2*$M;
		$maxnewh=$maxneww*$maxh/$maxw;
	}
	else {
		$maxnewh=$H-2*$M;
		$maxneww=$maxnewh*$maxw/$maxh;
	}
	
	for ($i=0;$i<count($points);$i++) {
		for ($j=0;$j<count($points[$i]);$j+=2) {
			// Отмасштабируем изображение
			//Пропорции не сохраняются
			//$points[$i][$j]*=($W-2*$M); 
			//$points[$i][$j+1]*=($H-2*$M);
			//Пропорции сохраняются
			$points[$i][$j]*=$maxneww;
			$points[$i][$j+1]*=$maxnewh;

			// Сдвинем изображение
			//Пропорции не сохраняются
			//$points[$i][$j]+=$M;
			//$points[$i][$j+1]+=$M;
			//Пропорции сохраняются
			$points[$i][$j]+=($W-$maxneww)/2;
			$points[$i][$j+1]+=($H-$maxnewh)/2;		
		}
	}
	
	//Отрисовка полигонов
	for ($i=0;$i<count($points);$i++) {
		imagefilledpolygon($im, $points[$i], count($points[$i])/2, imagecolorallocate($im, $pcolor[$i][0], $pcolor[$i][1], $pcolor[$i][2]));
	}
	
	imagepng($im, "Moose.png");
	//imagepng($im);
	imagedestroy($im);
?>