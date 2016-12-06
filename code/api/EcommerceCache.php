<?php
/**
 * to do: separate into MEMORY and NON-MEMORY
 *
 */

class EcommerceCache extends Object implements flushable
{

    private static $cache_in_mysql_tables = array(
    );

    public static function flush()
    {
        $cache = SS_Cache::factory('any');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(is_array($tables)) {
            foreach($tables as $table) {
                $table = self::make_mysql_table_name($table);
                DB::query(
                    '
                    DROP TABLE IF EXISTS "'.$table.'";
                    '
                );
                DB::query(
                    '
                    CREATE TABLE "'.$table.'" (
                      "PAGEID" int(11) NOT NULL,
                      "CACHEKEY" CHAR(50) NOT NULL,
                      "DATA" TEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                    '
                );

                DB::query(
                    '
                    ALTER TABLE "'.$table.'"
                      ADD PRIMARY KEY ("PAGEID","CACHEKEY"),
                      ADD KEY "CACHEKEY" ("CACHEKEY"),
                      ADD KEY "PAGEID" ("PAGEID");
                    '
                );
            }
        }
    }

    private static function clean()
    {
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(is_array($tables)) {
            foreach($tables as $table) {
                $table = self::make_mysql_table_name($table);
                DB::query(
                    '
                    DELETE FROM "'.$table.'";
                    '
                );
            }
        }
    }

    private static $_items = array();

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $cacheKey
     * @return mixed
     */
    public static function load($table, $id, $cacheKey)
    {
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(is_array($tables) && in_array($table, $tables)) {
            $table = self::make_mysql_table_name($table);
            $id = (int)$id;
            if(isset(self::$_items[$table])) {
                if(isset(self::$_items[$table][$id])) {
                    if(isset(self::$_items[$table][$id][$cacheKey])) {
                        if(self::$_items[$table][$id][$cacheKey] !== null) {
                            return @unserialize(self::$_items[$table][$id][$cacheKey]);
                        }
                    }
                }
            }

            //we are now loading the data ...
            if(!isset(self::$_items[$table])) {
                self::$_items[$table] = array();
            }
            if( ! isset(self::$_items[$table][$id])) {
                self::$_items[$table][$id] = array();
                $rows = DB::query('SELECT "CACHEKEY", "DATA" FROM "'.$table.'" WHERE "PAGEID" = '.$id.' ;');
                foreach($rows as $row) {
                    self::$_items[$table][$id][$row['CACHEKEY']] = $row['DATA'];
                }
                //return the value, if there is one.
                if(isset(self::$_items[$table][$id])) {
                    if(isset(self::$_items[$table][$id][$cacheKey])) {
                        return @unserialize(self::$_items[$table][$id][$cacheKey]);
                    }
                }
            }
        } else {
            $cache = SS_Cache::factory($table.'_'.$id);
            $data = $cache->load($cacheKey);
            if (!$data) {
                return;
            }

            return @unserialize($data);
        }
    }

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $cacheKey
     * @param mixed            $data
     *
     */
    public static function save($table, $id, $cacheKey, $data)
    {
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(is_array($tables) && in_array($table, $tables)) {
            $table = self::make_mysql_table_name($table);
            $id = (int)$id;
            if(strlen($cacheKey) > 50) {
                user_error('ERROR: CACHEKEY longer than 50 characters: '.$cacheKey);
            }
            $data = Convert::raw2sql(serialize($data));
            if(!isset(self::$_items[$table])) {
                self::$_items[$table] = array();
            }
            if(isset(self::$_items[$table][$id])) {
                self::$_items[$table][$id] = array();
            }
            self::$_items[$table][$id][$cacheKey] = $data;
            DB::query('
                INSERT INTO "'.$table.'" ("PAGEID", "CACHEKEY", "DATA")
                VALUES ('.$id.', \''.$cacheKey.'\', \''.$data.'\')
                ON DUPLICATE KEY UPDATE DATA = \''.$data.'\';
            ');
        } else {
            $cache = SS_Cache::factory($table."_".$id);
            $data = serialize($data);
            $cache->save($data, $cacheKey);
        }
    }

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $cacheKey
     * @param mixed            $data
     */
    public static function touch($table, $id, $cacheKey, $data)
    {
        $data = self::load($table, $inty, $cacheKey);
        self::save($table, $id, $cacheKey, $data);
    }

    private static function make_mysql_table_name($table)
    {
        return strtoupper($table). '_CACHE';
    }

}
