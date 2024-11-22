<?php

namespace Hfig\MAPI\OLE\Pear;

class StreamWrapper
{
    public const PROTOCOL = 'olewrap';

    private $stream;
    public $context;

    private string|bool|null $buffer = null;

    private static array $handles = [];

    public static function wrapStream($stream, $mode): string
    {
        self::register();

        $data            = ['mode' => $mode, 'stream' => $stream];
        self::$handles[] = $data;
        $key             = array_key_last(self::$handles);

        return 'olewrap://stream/'.(string) $key;
    }

    /**
     * @return resource
     */
    public static function createStreamContext($stream)
    {
        return stream_context_create([
            'olewrap' => ['stream' => $stream],
        ]);
    }

    public static function register(): void
    {
        if (!in_array('olewrap', stream_get_wrappers())) {
            stream_wrapper_register('olewrap', self::class);
        }
    }

    public function stream_cast($cast_as)
    {
        return $this->stream;
    }

    public function stream_open($path, $mode, $options, &$opened_path): bool
    {
        $url        = parse_url((string) $path);
        $streampath = [];
        $handle     = null;

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

            return true;
        }

        return false;
    }

    public function stream_read($count): string
    {
        // always read a block to satisfy the buffer
        $this->buffer = fread($this->stream, 8192);

        return substr($this->buffer, 0, $count);
    }

    public function stream_write($data): int|false
    {
        return fwrite($this->stream, (string) $data);
    }

    public function stream_tell(): int|false
    {
        return ftell($this->stream);
    }

    public function stream_eof(): bool
    {
        return feof($this->stream);
    }

    public function stream_seek($offset, $whence): int
    {
        // echo 'seeking on parent stream (' . $offset . '  ' . $whence . ')'."\n";
        return fseek($this->stream, $offset, $whence);
    }

    public function stream_stat(): array|false
    {
        return fstat($this->stream);
    }

    public function url_stat($path, $flags): array
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
            'blocks'  => 0,
        ];
    }
}
