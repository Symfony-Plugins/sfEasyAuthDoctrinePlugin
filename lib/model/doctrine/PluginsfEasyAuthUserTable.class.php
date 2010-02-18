<?php
/**
 */
class PluginsfEasyAuthUserTable extends Doctrine_Table
{
  /**
   * Returns an array of possible user types
   * 
   * @return array
   */
  public static function getTypes()
  {
    $types = array();
    $authReflector = new ReflectionClass('sfEasyAuthUser');
echo "PluginsfEasyAuthUserTable::getTypes() check this code";exit;
    foreach (scandir(dirname(__FILE__)) as $file)
    {
      $file = pathinfo($file, PATHINFO_FILENAME);
      
      if (strpos($file, 'sfEasyAuth') === 0 && strpos($file, 'Peer') === false)
      {
        // make sure the class inherits from sfEasyAuthUser
        $reflector = new ReflectionClass($file);
        if ($reflector->isSubClassOf($authReflector))
        {
          $type = str_replace('sfEasyAuth', '', $file);
          $typeName = preg_replace('/^(.)/e', 'strtolower("$1")', $type);
          $types[$typeName] = $type;
        }
      }
    }

    return $types;
  }
  
  /**
   * Retrieves a user by ID and auto-log-in hash
   * @param int $id
   * @param string $hash
   * @return mixed
   */
  public static function findOneByIdAndAutoLoginHash($id, $hash)
  {
    $q = Doctrine::create()
      ->from('sfEasyAuthUser')
      ->where('id='. $id)
      ->andWhere('auto_login_hash=' . $hash);
      
    return $q->executeOne();
  }
}