<?php


namespace ResellerInterface\Api\Response;


class ResponseDownload extends Response
{
    /**
     * ResponseDownload constructor.
     *
     * @param $rawData
     * @param $fileName
     * @param $fileSize
     * @param $fileType
     */
    public function __construct($rawData, $fileName, $fileSize, $fileType)
    {
        $this->response = [
            'state'      => 1000,
            'stateParam' => "",
            'stateName'  => "OK",
            'file'       => $rawData,
            'fileName'   => $fileName,
            'fileSize'   => $fileSize,
            'fileType'   => $fileType,
        ];
    }

    /**
     * @return mixed|null
     */
    public function getFile()
    {
        return isset($this->response['file']) ? $this->response['file'] : null;
    }

    /**
     * @return int|null
     */
    public function getFileSize()
    {
        return isset($this->response['fileSize']) ? $this->response['fileSize'] : null;
    }

    /**
     * @return string|null
     */
    public function getFileName()
    {
        return isset($this->response['fileName']) ? $this->response['fileName'] : null;
    }

    /**
     * @return string|null
     */
    public function getFileType()
    {
        return isset($this->response['fileType']) ? $this->response['fileType'] : null;
    }

}