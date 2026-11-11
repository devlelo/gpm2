<?php
declare(strict_types=1);
// Execute apenas uma vez através do terminal: php database/create_admin.php
require_once __DIR__.'/../config/database.php';
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Execute este ficheiro apenas no terminal.');}
$name=trim(readline('Nome do administrador: '));$email=trim(readline('Email do administrador: '));$password=readline('Palavra-passe (mínimo 12 caracteres): ');
if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12){fwrite(STDERR,"Dados inválidos.\n");exit(1);}
$pdo=db();$s=$pdo->prepare('SELECT id FROM users WHERE email=?');$s->execute([$email]);if($s->fetch()){fwrite(STDERR,"Já existe uma conta com esse email; nada foi alterado.\n");exit(1);}
$s=$pdo->prepare('INSERT INTO users(name,email,password,role,active,must_change_password) VALUES(?,?,?,\'admin\',1,0)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);echo "Administrador criado com sucesso. Guarde as credenciais em segurança.\n";
