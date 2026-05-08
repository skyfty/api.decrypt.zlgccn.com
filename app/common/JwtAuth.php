<?php
namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;
use think\facade\Log; // 引入日志

class JwtAuth 
{
    // 从配置文件获取密钥
    private static function getAccessKey()
    {
        return Config::get('jwt.access_key');
    }
    
    private static function getRefreshKey()
    {
        return Config::get('jwt.refresh_key');
    }
    
    /**
     * 获取访问令牌有效期（新增方法）
     */
    public static function getAccessExpire()
    {
        return Config::get('jwt.access_expire', 3600);
    }

    /**
     * 获取刷新令牌有效期（新增方法）
     */
    public static function getRefreshExpire()
    {
        return Config::get('jwt.refresh_expire', 604800);
    }

    /**
     * 生成访问令牌 (短期)
     */
    public static function generateAccessToken($payload, $expire = null)
    {
        $expire = $expire ?? Config::get('jwt.access_expire');
        $time = time();
        $token = [
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + $expire,
            'type' => 'access',
            'data' => $payload
        ];
        return JWT::encode($token, self::getAccessKey(), 'HS256');
    }

    /**
     * 生成刷新令牌 (长期)
     */
    public static function generateRefreshToken($payload, $expire = null)
    {
        $expire = $expire ?? Config::get('jwt.refresh_expire');
        $time = time();
        $token = [
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + $expire,
            'type' => 'refresh',
            'data' => $payload
        ];
        return JWT::encode($token, self::getRefreshKey(), 'HS256');
    }

    // 验证方法保持不变，仅密钥获取方式修改
    public static function verifyAccessToken($token)
    {
        try {
            Log::warning('令牌验证'.$token);
            $decoded = JWT::decode($token, new Key(self::getAccessKey(), 'HS256'));
            $decodedArray = (array)$decoded; 
            
            if ($decodedArray['type'] !== 'access') {
                return false;
            }
            
            $data = $decodedArray['data'];
            return is_object($data) ? (array)$data : $data;
        } catch (\Exception $e) {
            
            Log::error('JWT 验证异常: ' . $e->getMessage(), [
                'token' => $token,
                'exception' => get_class($e)
            ]);
            return false;
        }
    }

    public static function verifyRefreshToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getRefreshKey(), 'HS256'));
            $decodedArray = (array)$decoded;
            
            if ($decodedArray['type'] !== 'refresh') {
                return false;
            }
            
            $data = $decodedArray['data'];
            return is_object($data) ? (array)$data : $data;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function generateTokenPair($payload)
    {
        return [
            'access_token' => self::generateAccessToken($payload),
            'refresh_token' => self::generateRefreshToken($payload),
            'expires_in' => Config::get('jwt.access_expire')
        ];
    }
}