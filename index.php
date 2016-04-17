<?php

require('vendor/autoload.php');

use Symfony\Component\Yaml\Yaml;

$role_map = array('beetbox' => array());
$vars = array();
$config_files = array(
    'beetbox.config.yml',
    'project.config.yml',
    'vagrant.config.yml',
    'local.config.yml',
);

// Get role default vars.
foreach (glob('/beetbox/provisioning/ansible/roles/*', GLOB_ONLYDIR) as $role_path)
{
    $role = basename($role_path);
    $defaults_file = $role_path . '/defaults/main.yml';
    if (file_exists($defaults_file)) {
        $defaults = Yaml::parse(file_get_contents($defaults_file));
        if (count($defaults) > 0) {
            $role_map[$role] = $defaults;
            $vars = array_merge($vars, $defaults);
        }
    }
}

// Get beetbox overrides.
foreach ($config_files as $file)
{
    $conf = '/beetbox/provisioning/ansible/config/' . $file;
    if (file_exists($conf)) {
        $overrides = Yaml::parse(file_get_contents($conf));
        if (count($overrides) > 0) {
            foreach($overrides as $override => $value) {
                if (!isset($vars[$override])) {
                    $role_map['beetbox'][$override] = $value;
                }
                $vars[$override] = $value;
            }
        }
    }
}

// Convert array vars to YAML.
foreach ($vars as $var => $val)
{
    if (is_array($val)) {
        $vars[$var] = "\n" . Yaml::dump($val, 1);
    }
}

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

echo $twig->render('html.twig', array('role_map' => $role_map, 'vars' => $vars));
