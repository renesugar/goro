<?php

// make ext.go files for all exts

$dh = opendir('ext');
if (!$dh) die("could not open dir\n");

while(($ext = readdir($dh)) !== false) {
	if (($ext == '.') || ($ext == '..')) continue;

	$constants = [];
	$functions = [];

	// gather files in ext
	$dh2 = opendir('ext/'.$ext);
	if (!$dh2) die("could not open dir for $ext\n");

	while(($f = readdir($dh2)) !== false) {
		if (($f == '.') || ($f == '..')) continue;
		if ($f == 'ext.go') continue; // skip
		if (substr($f, -3) != '.go') continue; // skip non .go files

		$fp = fopen('ext/'.$ext.'/'.$f, 'r');
		if (!$fp) die("failed to open $f\n");

		$lineno = 0;
		$function_pending = NULL;

		while(!feof($fp)) {
			$lineno += 1;
			$lin = fgets($fp);
			if ((substr($lin, 0, 5) == 'func ') && (!is_null($function_pending))) {
				// we have a function name
				$lin = substr($lin, 5);
				$pos = strpos($lin, '(');
				if ($pos===false) die("failed to parse function line func $lin\n");
				$func_name = substr($lin, 0, $pos);
				$functions[$function_pending]['val'] = $func_name;
				$function_pending = NULL;
				continue;
			}
			if (substr($lin, 0, 4) != '//> ') continue;
			$lin = trim(substr($lin, 4));
			$pos = strpos($lin, ' ');
			if ($pos === false) die("failed to parse $lin\n");
			$code = substr($lin, 0, $pos);
			$lin = trim(substr($lin, $pos+1));

			switch($code) {
				case 'const':
					// $lin is : <code> <value> [ // possible comment]
					$pos = strpos($lin, '//');
					if ($pos !== false) {
						$lin = trim(substr($lin, 0, $pos));
					}
					$pos = strpos($lin, ':');
					if ($pos === false) {
						die("failed to parse const $lin (no :)\n");
					}
					$const = trim(substr($lin, 0, $pos));
					$val = trim(substr($lin, $pos+1));
					$constants[$const] = ['val' => $val, 'where' => $f.':'.$lineno];
					break;
				case 'func':
					// $lin is: <return_type> <function_name> ( <arguments> )
					$pos = strpos($lin, ' ');
					if ($pos === false) {
						die("failed to parse func $lin (no space)\n");
					}
					$type = trim(substr($lin, 0, $pos));
					$lin = trim(substr($lin, $pos+1));

					$pos = strpos($lin, ' ');
					if ($pos === false) {
						die("failed to parse func $lin (no space)\n");
					}
					$func = trim(substr($lin, 0, $pos));
					$lin = trim(substr($lin, $pos+1));

					// TODO args
					$functions[strtolower($func)] = ['val' => null, 'where' => $f.':'.$lineno];
					$function_pending = strtolower($func);
					break;
				default:
					die("failed to parse $code $lin (unknown code)\n");
			}
		}
		fclose($fp);
		if (!is_null($function_pending)) die("failed to find implementation of $function_pending");
	}

	$fp = fopen('ext/'.$ext.'/ext.go~', 'w');
	fwrite($fp, 'package '.$ext."\n\n");
	fwrite($fp, "import \"github.com/MagicalTux/gophp/core\"\n\n"); // other imports will be handled automatically at build time
	fwrite($fp, "// WARNING: This file is auto-generated. DO NOT EDIT\n\n");
	fwrite($fp, "func init() {\n");
	fwrite($fp, "\tcore.RegisterExt(&core.Ext{\n");
	fwrite($fp, "\t\tName: \"".addslashes($ext)."\",\n"); // addslashes not quite equivalent to go's %q

	fwrite($fp, "\t\tFunctions: map[string]*core.ExtFunction{\n");
	foreach($functions as $func => $info) {
		// sample args: Args: []*core.ExtFunctionArg{&core.ExtFunctionArg{ArgName: "output"}, &core.ExtFunctionArg{ArgName: "...", Optional: true}}
		fwrite($fp, "\t\t\t\"".addslashes($func)."\": &core.ExtFunction{Func: ".$info['val'].", Args: []*core.ExtFunctionArg{}}, // ".$info['where']."\n"); // TODO args
	}
	fwrite($fp, "\t\t},\n");

	fwrite($fp, "\t\tConstants: map[core.ZString]*core.ZVal{\n");
	foreach($constants as $const => $info) {
		fwrite($fp, "\t\t\t\"".addslashes($const)."\": ".$info['val'].".ZVal(), // ".$info['where']."\n");
	}
	fwrite($fp, "\t\t},\n");
	fwrite($fp, "\t})\n");
	fwrite($fp, "}\n");
	fclose($fp);

	// rename
	rename('ext/'.$ext.'/ext.go~', 'ext/'.$ext.'/ext.go');
}
