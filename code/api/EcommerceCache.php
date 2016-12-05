<?php


class EcommerceCache extends Object implements flushable
{

    private static $cache_in_mysql_tables = array(
        'ProductGroup'
    );

    public static function flush()
    {
        $cache = SS_Cache::factory('any');
        $cache->clean(Zend_Cache::CLEANING_MODE_ALL);        
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
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
                  "KEY" CHAR(50) NOT NULL,
                  "DATA" TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                '
            );

            DB::query(
                '
                ALTER TABLE "'.$table.'"
                  ADD PRIMARY KEY ("PAGEID","KEY"),
                  ADD KEY "KEY" ("KEY");
                '
            );
        }
    }

    private static $_items = array();

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $key
     * @return mixed
     */
    public static function load($table, $id, $key)
    {
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(in_array($table, $tables)) {
            $table = self::make_mysql_table_name($table);
            $id = (int)$id;
            if(isset(self::$_items[$table])) {
                if(isset(self::$_items[$table][$id])) {
                    if(isset(self::$_items[$table][$id][$key])) {
                        if(self::$_items[$table][$id][$key] !== null) {
                            return @unserialize(self::$_items[$table][$id][$key]);
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
                $rows = DB::query('SELECT "KEY", "DATA" FROM "'.$table.'" WHERE "PAGEID" = '.$id.' ;');
                foreach($rows as $row) {
                    self::$_items[$table][$id][$row['KEY']] = $row['DATA'];
                }
                //return the value, if there is one.
                if(isset(self::$_items[$table][$id])) {
                    if(isset(self::$_items[$table][$id][$key])) {
                        return @unserialize(self::$_items[$table][$id][$key]);
                    }
                }
            }
        } else {
            $cache = SS_Cache::factory($table.'_'.$id);
            $data = $cache->load($key);
            if (!$data) {
                return;
            }

            return @unserialize($data);
        }
    }

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $key
     * @param mixed            $data
     *
     */
    public static function save($table, $id, $key, $data)
    {
        $tables = Config::inst()->get('EcommerceCache', 'cache_in_mysql_tables');
        if(in_array($table, $tables)) {
            $table = self::make_mysql_table_name($table);
            $id = (int)$id;
            if(strlen($key) > 50) {
                user_error('ERROR: key longer than 50 characters: '.$key);
            }
            $data = Convert::raw2sql(serialize($data));
            if(!isset(self::$_items[$table])) {
                self::$_items[$table] = array();
            }
            if(isset(self::$_items[$table][$id])) {
                self::$_items[$table][$id] = array();
            }
            self::$_items[$table][$id][$key] = $data;
            DB::query('
                INSERT INTO "'.$table.'" ("PAGEID", "KEY", "DATA")
                VALUES ('.$id.', \''.$key.'\', \''.$data.'\')
                ON DUPLICATE KEY UPDATE DATA = \''.$data.'\';
            ');
        } else {
            $cache = SS_Cache::factory($table."_".$id);
            $data = serialize($data);
            $cache->save($data, $key);
        }
    }

    /**
     * @param string           $table
     * @param int              $id
     * @param string           $key
     * @param mixed            $data
     */
    public static function touch($table, $id, $key, $data)
    {
        $data = self::load($table, $inty, $key);
        self::save($table, $id, $key, $data);
    }

    private static function make_mysql_table_name($table)
    {
        return strtoupper($table). '_CACHE';
    }

}
