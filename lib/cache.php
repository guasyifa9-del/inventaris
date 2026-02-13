<?php
/**
 * Redis Cache Manager
 * High-performance caching layer untuk improve application performance
 * 
 * INSTALASI:
 * 1. Install Redis server: apt-get install redis-server
 * 2. Install PHP Redis extension: pecl install redis
 * 3. Enable extension: echo "extension=redis.so" > /etc/php/8.1/cli/conf.d/20-redis.ini
 * 
 * KONFIGURASI di .env:
 * REDIS_HOST=127.0.0.1
 * REDIS_PORT=6379
 * REDIS_PASSWORD=
 * REDIS_DB=0
 * CACHE_TTL=3600
 */

class CacheManager {
    private $redis;
    private $enabled;
    private $prefix;
    private $defaultTTL;
    
    public function __construct($prefix = 'inventaris:', $ttl = 3600) {
        $this->prefix = $prefix;
        $this->defaultTTL = $ttl;
        $this->enabled = false;
        
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                $port = (int)(getenv('REDIS_PORT') ?: 6379);
                $password = getenv('REDIS_PASSWORD');
                
                if ($this->redis->connect($host, $port, 2.5)) {
                    if ($password) {
                        $this->redis->auth($password);
                    }
                    
                    $db = (int)(getenv('REDIS_DB') ?: 0);
                    $this->redis->select($db);
                    
                    $this->enabled = true;
                    error_log("Redis cache enabled successfully");
                } else {
                    error_log("Redis connection failed");
                }
            } else {
                error_log("Redis extension not installed");
            }
        } catch (Exception $e) {
            error_log("Redis initialization error: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Get value from cache
     */
    public function get($key) {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            $value = $this->redis->get($fullKey);
            
            if ($value === false) {
                return null;
            }
            
            // Try to unserialize
            $unserialized = @unserialize($value);
            return $unserialized !== false ? $unserialized : $value;
            
        } catch (Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Set value in cache
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            $ttl = $ttl ?: $this->defaultTTL;
            
            // Serialize complex data types
            if (is_array($value) || is_object($value)) {
                $value = serialize($value);
            }
            
            return $this->redis->setex($fullKey, $ttl, $value);
            
        } catch (Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete key from cache
     */
    public function delete($key) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            return $this->redis->del($fullKey) > 0;
            
        } catch (Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete multiple keys matching pattern
     */
    public function deletePattern($pattern) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullPattern = $this->prefix . $pattern;
            $keys = $this->redis->keys($fullPattern);
            
            if (empty($keys)) {
                return true;
            }
            
            return $this->redis->del($keys) > 0;
            
        } catch (Exception $e) {
            error_log("Cache delete pattern error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if key exists
     */
    public function exists($key) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            return $this->redis->exists($fullKey) > 0;
            
        } catch (Exception $e) {
            error_log("Cache exists error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or set cache (remember pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Increment counter
     */
    public function increment($key, $value = 1) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            return $this->redis->incrBy($fullKey, $value);
            
        } catch (Exception $e) {
            error_log("Cache increment error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decrement counter
     */
    public function decrement($key, $value = 1) {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $fullKey = $this->prefix . $key;
            return $this->redis->decrBy($fullKey, $value);
            
        } catch (Exception $e) {
            error_log("Cache decrement error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache with prefix
     */
    public function flush() {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            $pattern = $this->prefix . '*';
            $keys = $this->redis->keys($pattern);
            
            if (empty($keys)) {
                return true;
            }
            
            return $this->redis->del($keys) > 0;
            
        } catch (Exception $e) {
            error_log("Cache flush error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function stats() {
        if (!$this->enabled) {
            return null;
        }
        
        try {
            $info = $this->redis->info();
            return [
                'enabled' => $this->enabled,
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'total_keys' => $this->redis->dbSize(),
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info)
            ];
            
        } catch (Exception $e) {
            error_log("Cache stats error: " . $e->getMessage());
            return null;
        }
    }
    
    private function calculateHitRate($info) {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($hits / $total) * 100, 2);
    }
}

// Global cache instance
$cache = new CacheManager('inventaris:', 3600);

/**
 * Helper functions for easy cache access
 */

function cache_get($key) {
    global $cache;
    return $cache->get($key);
}

function cache_set($key, $value, $ttl = null) {
    global $cache;
    return $cache->set($key, $value, $ttl);
}

function cache_delete($key) {
    global $cache;
    return $cache->delete($key);
}

function cache_remember($key, $callback, $ttl = null) {
    global $cache;
    return $cache->remember($key, $callback, $ttl);
}

function cache_flush() {
    global $cache;
    return $cache->flush();
}

/**
 * Cached query helper for database operations
 */
function cached_query($connection, $key, $query, $ttl = 3600) {
    return cache_remember($key, function() use ($connection, $query) {
        $result = mysqli_query($connection, $query);
        if (!$result) {
            return null;
        }
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }, $ttl);
}

/**
 * Example usage in application:
 * 
 * // Cache barang list
 * $barang = cache_remember('barang:list:active', function() use ($connection) {
 *     $result = mysqli_query($connection, "SELECT * FROM barang WHERE status = 'active'");
 *     return mysqli_fetch_all($result, MYSQLI_ASSOC);
 * }, 1800); // 30 minutes
 * 
 * // Cache kategori (rarely changes)
 * $kategori = cache_remember('kategori:all', function() use ($connection) {
 *     $result = mysqli_query($connection, "SELECT * FROM kategori ORDER BY nama_kategori");
 *     return mysqli_fetch_all($result, MYSQLI_ASSOC);
 * }, 86400); // 24 hours
 * 
 * // Invalidate cache when data changes
 * // After updating barang:
 * cache_delete('barang:list:active');
 * cache_delete('barang:' . $barang_id);
 * 
 * // Get statistics
 * $stats = $cache->stats();
 */

/**
 * Integration with existing code:
 * 
 * 1. In admin/barang/index.php, wrap the query:
 * 
 * $barang = cache_remember('barang:list:' . md5(serialize($_GET)), function() use ($connection, $query) {
 *     $result = mysqli_query($connection, $query);
 *     return mysqli_fetch_all($result, MYSQLI_ASSOC);
 * }, 300); // 5 minutes
 * 
 * 2. In admin/barang/edit.php, invalidate cache after update:
 * 
 * cache_delete('barang:' . $barang_id);
 * cache_deletePattern('barang:list:*');
 * 
 * 3. In user/barang/index.php, cache available barang:
 * 
 * $barang = cache_remember('barang:available', function() use ($connection) {
 *     $query = "SELECT * FROM barang WHERE status = 'active' AND jumlah_tersedia > 0";
 *     $result = mysqli_query($connection, $query);
 *     return mysqli_fetch_all($result, MYSQLI_ASSOC);
 * }, 600); // 10 minutes
 */
