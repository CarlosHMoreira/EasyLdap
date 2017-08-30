<?php

abstract class ldapConn {
            
    private function __construct() {}
     /**
     * Tenta uma conexão com o LDAP e usa o $login e $passwd para validar as credenciais.
     * @param {String} $login contem o dado (nome/idt) para autenticação .
     * @param {String} $passwd  senha para autenticar. 
     * @returns {String/Boleano|serverResponse} Retorna 1 se sucesso, caso erro retorna uma String com uma descrição do erro.
     */
    static function ldapStartConn(){
        $host = "255.255.255.255"; 
        $con = ldap_connect($host); 
        ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
        return $con;
    }
    
     /**
     * Busca um usuário pela sua identidade na base do ldap.
     * @param {String} $login contem o dado (nome/idt) para autenticação .
     * @returns {String/Boleano} Retorna um String com o caminho (dn) do usuário no servidor ldap.</br>
      * caso erro retorna FALSE.
     **/
    static function ldapFindUser($login){
        
        $ldap = self::ldapStartConn();
        $config['dc']= "dc=caminho,dc=da,dc=sua,dc=base";
        $config['filter'] = "(uid=".$login.")";
        //$config['attr'] = array("ou");
       
        $uid = ldap_search($ldap,$config['dc'],$config['filter']);
        $user = ldap_get_entries($ldap, $uid);
        //Se quiser da um echo $user;exit(); Pra ver a estrutura que retorna.
        if($user['count']<= 0){
            @ldap_unbind($ldap); 
            return false;
        }
        //@ldap_unbind($ldap);   
        return $user[0]['dn'];
    }
    
    /**
     * Autentica um usuário.
     * @param {String} $login contem o dado (nome/idt) para autenticação .
     * @param {String} $passwd senha do usuário. 
     * @returns {String/Boleano|serverResponse} Retorna 1 se sucesso, caso erro retorna uma String com uma descrição do erro.
     */
    static function ldapAuth($login, $passwd){
        
        try{
            $ldap = self::ldapStartConn();              
            $user = self::ldapFindUser($login);
            
            
            if(!$user)
                throw new Exception("Usuário incorreto ou  não cadastrado.");

            if(!$bd = @ldap_bind($ldap, $user, $passwd )){
                $error = error_get_last();

                //mapeei os 2 principais erros que vem além do erro acima aí oh! Se for comum outro erro aí só // adicionar um outro case, ou se tiver paciencia mapeie todos.
                switch ($error['message']){
                    
                    case "ldap_bind(): Unable to bind to server: Can't contact LDAP server":
                        throw new Exception("Error ao conectar com ldap.");
                        break;
                    
                    case "ldap_bind(): Unable to bind to server: Invalid credentials":
                        throw new Exception("Senha incorreta.");
                        break;
                    
                    default:
                       throw new Exception("Erro não identificao, procure o administrador.");
                }
            }
            @ldap_unbind($ldap);
            return 1;
        }catch(Exception $e){
            @ldap_unbind($ldap); 
            return $e->getMessage();
        }
    }
     /**
     * Tenta alterar a senha de usuário..
     * @param {String} $login contem o dado (nome/idt) para autenticação .
     * @param {String} $newPassword  nova senha de usuário. 
     * @returns {String/Boleano} Retorna 1 se sucesso, caso erro retorna uma String com uma descrição do erro.
     */
    static function ldapUpdatePassword($login, $newPassword){
        
        $ldap = self::ldapStartConn();              
        $user = self::ldapFindUser($login);
        
        $config['admin'] = "cn=NomeDaContaAdmin,dc=Caminho,dc=da,dc=sua,dc=base";
        $config['adminPass'] = "senhaDoMisterFuckYeahAdmin";
        
        $password['userpassword'] = $newPassword;
        
        $bd = @ldap_bind($ldap,$config['admin'],$config['adminPass'] );//Utilizando credenciais de usuário admin ldap
        
    
        try {
            if(!$ldap)
                throw new Exception("Erro ao conectar com ldap.");
            
            if(!$user)
                throw new Exception("Usuário incorreto ou não cadastrado.");
                        
            if(!$bd)
                throw new Exception("Erro de autorização.<br>Entre em contato com o Administrador");
                        
            $result = @ldap_modify($ldap,$user,$password);
           
            if(!$result) 
               throw new Exception("Não foi possível alterar a senha.<br>Entre em contato com o Administrador");
            
            @ldap_unbind($ldap);
            return 1;
        } catch (Exception $e) {
            @ldap_unbind($ldap);
            return $e->getMessage();
        }
    }
}

