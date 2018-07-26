<?php

namespace Hfig\MAPI\OLE\Pear;

class StreamWrapper 
{
    const PROTOCOL = 'olewrap';

    private $stream;
    public $context;
    private $mode;
    private $buffer;
    private $position;


    private static $handles = [];

    public static function wrapStream($stream, $mode)
    {
        self::register();

        $data = ['mode' => $mode, 'stream' => $stream];
        self::$handles[] = $data;

        end(self::$handles);
        $key = key(self::$handles);

        return 'olewrap://stream/' . (string)$key;

    }

    public static function createStreamContext($stream)
    {
        return stream_context_create([
            'olewrap' => ['stream' => $stream]
        ]);
    }

    public static function register()
    {
        if (!in_array('olewrap', stream_get_wrappers())) {
            stream_wrapper_register('olewrap', __CLASS__);
        }
    }

    public function stream_cast($cast_as)
    {
        return $this->stream;
    }


    public function stream_open($path, $mode, $options, &$opened_path) 
    { 
        $url = parse_url($path);
        $streampath = [];
        $handle = null;
        
        
        if (isset($url['path'])) {
            $streampath = explode('/', $url['path']);
        }
        if (isset($streampath[1])) {
            $handle = $streampath[1];
        }
        if (isset($handle) && isset(self::$handles[$handle])) {
            $this->stream = self::$handles[$handle]['stream'];

            if ($mode[0] == 'r' || $mode[0] == 'a') {
                fseek($this->stream, 0);            
            }

            $this->buffer = '';
            $this->position = 0;
                    
            

            return true;
        }

        return false;
    }

    public function stream_read($count)
    {
        // always read a block to satisfy the buffer
        $this->buffer = fread($this->stream, 8192);


        return substr($this->buffer, 0, $count);
    }

    public function stream_write($data)
    {
        return fwrite($this->stream, $data);
    }

    public function stream_tell()
    {
        return ftell($this->stream);
    }

    public function stream_eof()
    {
        return feof($this->stream);
    }

    public function stream_seek($offset, $whence)
    {
        //echo 'seeking on parent stream (' . $offset . '  ' . $whence . ')'."\n";
        return fseek($this->stream, $offset, $whence);
    }

    public function stream_stat()
    {
        return fstat($this->stream);
    }

    public function url_stat($path, $flags)
    {
        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 0,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0
        ];
    }
}