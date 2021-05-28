<?php

namespace YesWiki\Lms\Service;

use YesWiki\Bazar\Field\TextareaField;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\PageManager;
use YesWiki\Wiki;

class ImportManager
{
    protected $wiki;
    protected $peertubeToken;


    /**
     * ImportManager constructor
     * @param Wiki $wiki the injected Wiki instance
     */
    public function __construct(Wiki $wiki)
    {
        $this->wiki = $wiki;
        $this->peertubeToken = null;
        $this->uploadPath = null;
    }

    /**
     * Fetch bazar entries from api
     *
     * @param string $remoteUrl distant url
     * @param string $remoteToken API token
     * @param string $apiArgs api path to entries and format, usually fiche/<formid>/html
     * @return array Array of entries with id_fiche index
     */
    public function fetchEntriesFromApi($remoteUrl, $remoteToken, $apiArgs)
    {
        // Create a stream
        $opts = array(
            'http'=>array(
                'method' => 'GET',
                'header' => 'Authorization: Bearer ' . $remoteToken . "\r\n"
            )
        );

        $context = stream_context_create($opts);

        // Fetching all information needed
        $dataStr = file_get_contents($remoteUrl.'?api/'.$apiArgs, false, $context);
        if (empty($dataStr)) {
            throw new \Exception(_t('LMS_ERROR_NO_DATA'));
        } elseif (!$dataJson=json_decode($dataStr, true)) {
            throw new \Exception(_t('LMS_ERROR_PARSING_DATA'), $dataStr);
        } else {
            $data = array();
            foreach ($dataJson as $entry) {
                $data[$entry['id_fiche']] = $entry;
            }
        }

        return $data;
    }

    /**
     * Get a token for the peertube API
     *
     * @return string the token
     */
    private function initPeertubeToken()
    {
        //initialise peertube token
        if (!empty($this->wiki->config['peertube_url'])
            && !empty($this->wiki->config['peertube_user'])
            && !empty($this->wiki->config['peertube_password'])
            && !empty($this->wiki->config['peertube_channel'])
        ) {
            // get token from peertube
            $peertubeUrl = $this->wiki->config['peertube_url'];
            $apiUrl = $peertubeUrl.'/api/v1/oauth-clients/local';
            $data_str = @file_get_contents($apiUrl);
            $token = json_decode($data_str, true);

            if (!empty($token['client_id']) && !empty($token['client_secret'])) {
                // Get user token
                $data = [
                    'client_id' => $token['client_id'],
                    'client_secret' => $token['client_secret'],
                    'grant_type' => 'password',
                    'response_type' => 'code',
                    'username' => $this->wiki->config['peertube_user'],
                    'password' => $this->wiki->config['peertube_password'],
                ];
                $opts = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data)
                    )
                );
                $context = stream_context_create($opts);
                $apiUrl = $peertubeUrl.'/api/v1/users/token';
                $dataStr = @file_get_contents($apiUrl, false, $context);
                $token = json_decode($dataStr, true);
                if (!empty($token['access_token'])) {
                    $this->peertubeToken = $token['access_token'];
                    return true;
                } else {
                    throw new \Exception(_t('LMS_ERROR_NO_PEERTUBE_TOKEN'));
                }
            } else {
                throw new \Exception(_t('LMS_ERROR_NO_CREDENTIALS').': '.$apiUrl);
            }
        }
        return false;
    }

    /**
     * Import given video url to peertube
     *
     * @param string $url source video url (can be peertube, youtube or vimeo)
     * @param string $title target video title
     * @return array API answer
     */
    public function importToPeertube($url, $title)
    {
        if ($this->peertubeToken === null) {
            $this->initPeertubeToken();
        }
        // Get channel id
        $channel = json_decode(file_get_contents($this->wiki->config['peertube_url'].'/api/v1/video-channels/'.$this->wiki->config['peertube_channel']), true);

        $data = [
            'channelId' => $channel['id'],
            'name' => $title,
            'targetUrl' => $url,
        ];
        $opts = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
                             "Authorization: Bearer ".$this->peertubeToken."\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($opts);
        $apiUrl = $this->wiki->config['peertube_url'].'/api/v1/videos/imports';
        $dataStr = file_get_contents($apiUrl, false, $context);
        return json_decode($dataStr, true);
    }

    /**
     * Get the local path to files uploads (usually "files")
     *
     * @return string local path to files uploads
     */
    private function getLocalFileUploadPath()
    {
        if ($this->uploadPath !== null) {
            return $this->uploadPath;
        }

        $attachConfig = $this->wiki->GetConfigValue("attach_config");

        if (!is_array($attachConfig)) {
            $attachConfig = array();
        }

        if (empty($attachConfig['upload_path'])) {
            $this->uploadPath = 'files';
        } else {
            $this->uploadPath = $attachConfig['upload_path'];
        }

        return $this->uploadPath;
    }

    /**
     * Download file url to local wiki using cURL
     *
     * @param string $from file url
     * @param string $to local path
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return void
     */
    private function cURLDownload($from, $to, $overwrite = false)
    {
        $output = '';
        if (file_exists($to)) {
            if ($overwrite) {
                $output .= _t('LMS_FILE').' '.$to.' '._t('LMS_FILE_OVERWRITE').'.';
            } else {
                $output .= _t('LMS_FILE').' '.$to.' '._t('LMS_FILE_NO_OVERWRITE').'.';
                return $output;
            }
        }

        // Do cURL transfer
        $fp = fopen($to, 'wb');
        $ch = curl_init($from);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if ($err) {
            unlink($to);
            throw new \Exception($output._t('LMS_ERROR_DOWNLOADING').' '.$from.': '.$err."\n"._t('LMS_REMOVING_CORRUPTED_FILE').' '.$to);
        }
        return $output;
    }

    /**
     * Return fields that may contain attachments to import (body for wikipage, or textelong fields for bazar entries)
     *
     * @param array $wikiPage page or entry content as an array
     * @return array keys of $wikiPage that may contain attachments to import
     */
    public function getTextFieldsFromWikiPage($wikiPage)
    {
        $fields= [];
        if (!empty($wikiPage['tag'])) { // classic wiki page
            $fields[] ='body';
        } elseif (!empty($wikiPage['id_fiche'])) { // bazar entry
            $formManager = $this->wiki->services->get(FormManager::class);
            $form = $formManager->getOne($wikiPage['id_typeannonce']);
            // find fields that are textareas
            foreach ($form['prepared'] as $field) {
                if ($field instanceof TextareaField) {
                    $fields[] = $field->getName();
                }
            }
        }
        return $fields;
    }

    /**
     * Get attachements from html_output that use direct links to /files folder
     * Also finds Bazar images
     *
     * @param string $remoteUrl distant url
     * @param array $wikiPage page or entry content as an array
     * @param boolean $transform transform attachments urls for their new location (default:false)
     * @return array attachments filenames
     */
    public function findDirectLinkAttachements($remoteUrl, &$wikiPage, $transform = false)
    {
        $regex = '#="'.preg_quote($remoteUrl, '#').'files/(?P<filename>.+)"#Ui';
        preg_match_all(
            $regex,
            $wikiPage['html_output'],
            $inlineAttachments
        );

        $bazarImages = array_filter($wikiPage, function ($k) {
            return str_starts_with($k, 'image');
        }, ARRAY_FILTER_USE_KEY);

        $attachments = array_merge($inlineAttachments['filename'], array_values($bazarImages));
        $attachments = array_unique($attachments);

        if ($transform) {
            $contentKeys = $this->getTextFieldsFromWikiPage($wikiPage);
            foreach ($contentKeys as $key) {
                $wikiPage[$key] = preg_replace(
                    $regex,
                    '="'.$this->wiki->getBaseUrl().'files/${filename}"',
                    $wikiPage[$key]
                );
            }
        }

        return $attachments;
    }

    /**
     * Generate distant file url and download to local file path
     *
     * @param string $remoteUrl distant file url
     * @param string $filename file name
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return void
     */
    public function downloadDirectLinkAttachment($remoteUrl, $filename, $overwrite = false)
    {
        $remoteFileUrl = $remoteUrl.'/files/'.$filename;
        $saveFileLoc = $this->getLocalFileUploadPath().'/'.$filename;

        return $this->cURLDownload($remoteFileUrl, $saveFileLoc, $overwrite);
    }

    /**
     * Find file attachments in page or bazar entry
     * It finds attachments linked with /download links
     *
     * @param string $remoteUrl distant url
     * @param array $wikiPage page or entry content as an array
     * @param boolean $transform transform attachments urls for their new location (default:false)
     * @return array all file attachments
     */
    public function findHiddenAttachments($remoteUrl, &$wikiPage, $transform = false)
    {
        preg_match_all(
            '#(?:href|src)="'.preg_quote($remoteUrl, '#').'\?.+/download&(?:amp;)?file=(?P<filename>.*)"#Ui',
            $wikiPage['html_output'],
            $htmlMatches
        );
        $attachments = $htmlMatches['filename'];

        $wikiRegex = '#="' . preg_quote($remoteUrl, '#')
                    . '(?P<trail>\?.+/download&(?:amp;)?file=(?P<filename>.*))"#Ui';

        $contentKeys = $this->getTextFieldsFromWikiPage($wikiPage);
        foreach ($contentKeys as $key) {
            preg_match_all($wikiRegex, $wikiPage[$key], $wikiMatches);
            $attachments = array_merge($attachments, $wikiMatches['filename']);
        }

        $attachments = array_unique($attachments);

        if ($transform) {
            foreach ($contentKeys as $key) {
                $wikiPage[$key] = preg_replace($wikiRegex, '="'.$this->wiki->getBaseUrl().'${trail}"', $wikiPage[$key]);
            }
        }

        return $attachments;
    }

    /**
     * Generate local path and download hidden attachments
     * It downloads attachments linked with /download links
     *
     * @param string $remoteUrl distant url
     * @param string $pageTag page tag
     * @param string $lastPageUpdate last update time
     * @param string $filename file name
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return array all file attachments
     */
    public function downloadHiddenAttachment($remoteUrl, $pageTag, $lastPageUpdate, $filename, $overwrite = false)
    {
        if (!class_exists('attach')) {
            require_once("tools/attach/libs/attach.lib.php");
        }

        $this->wiki->tag = $pageTag;
        $this->wiki->page = array('tag'=>$pageTag, 'time'=> $lastPageUpdate);

        $remoteFileUrl = $remoteUrl . '?' . $pageTag . '/download&file=' . $filename;
        $att = new \attach($this->wiki);
        $att->file = $filename;
        $newFilename = $att->GetFullFilename(true);

        $this->cURLDownload($remoteFileUrl, $newFilename, $overwrite);
    }

    /**
     * Find videos in page or bazar entry
     *
     * @param string $wikiPage page or bazar entry
     * @return array all videos
     */
    public function findVideos($wikiPage, $peertubeSource = '')
    {
        $videos = [];
        $videoWikiRegex = '#{{video(?:\s*(?:id="(?<id>\S+)"|serveur="(?<serveur>peertube|vimeo|youtube)"|peertubeinstance="(?<peertubeinstance>\S+)"|ratio="(?<ratio>.+)"|largeurmax="(?<largeurmax>\d+)"|hauteurmax="(?<hauteurmax>\d+)"| class="(?<class>.+)"))+\s*}}#i';
        $contentKeys = $this->getTextFieldsFromWikiPage($wikiPage);
        $allVideoWikiMatches = [];
        foreach ($contentKeys as $key) {
            preg_match_all(
                $videoWikiRegex,
                $wikiPage[$key],
                $videoWikiMatches
            );
            $allVideoWikiMatches = array_merge_recursive(
                $allVideoWikiMatches,
                $videoWikiMatches
            );
        }

        if (!empty($allVideoWikiMatches['id'])) {
            foreach ($allVideoWikiMatches['id'] as $index => $videoId) {
                // trouver l'instance video entre youtube|vimeo|peertube
                // creer l'url de la video et la mettre dans $videos[$index]['url']
                $videos[$index] = [];
                if (empty($allVideoWikiMatches['serveur'][$index])) {
                    if (strlen($videoId) == 11) {
                        $allVideoWikiMatches['serveur'][$index] = 'youtube';
                    } elseif (preg_match("/^\d+$/", $videoId)) {
                        $allVideoWikiMatches['serveur'][$index] = 'vimeo';
                    } else {
                        $allVideoWikiMatches['serveur'][$index] = 'peertube';
                    }
                }
                switch ($allVideoWikiMatches['serveur'][$index]) {
                    case 'youtube':
                        $videos[$index]['url'] = 'https://youtu.be/'.$videoId;
                        $video = json_decode(file_get_contents('https://noembed.com/embed?url='.$videos[$index]['url']), true);
                        $videos[$index]['title'] = $video['title'];
                        break;
                    case 'vimeo':
                        $videos[$index]['url'] = 'https://vimeo.com/'.$videoId;
                        $video = json_decode(file_get_contents('https://noembed.com/embed?url='.$videos[$index]['url']), true);
                        $videos[$index]['title'] = $video['title'];
                        break;
                    case 'peertube':
                        if (!empty($allVideoWikiMatches['peertubeinstance'][$index])) {
                            $videos[$index]['url'] = $allVideoWikiMatches['peertubeinstance'][$index].'/videos/watch/'.$videoId;
                        } else {
                            if (empty($peertubeSource)) {
                                $peertubeSource = $this->wiki->config['attach-video-config']['default_peertube_instance'];
                            }
                            $videos[$index]['url'] = $peertubeSource.'/videos/watch/'.$videoId;
                        }
                        $video = json_decode(file_get_contents(str_replace('videos/watch', 'api/v1/videos', $videos[$index]['url'])), true);
                        $videos[$index]['title'] = $video['name'];
                        break;

                    default:
                        throw new \Exception(_t('LMS_ERROR_PROVIDER').' "'.$allVideoWikiMatches[0][$index].'".');
                }
            }
        }

        $videoHtmlRegex = '#<iframe.+?(?:\s*width=["\'](?<width>[^"\']+)["\']|\s*height=["\'](?<height>[^\'"]+)["\']|\s*src=["\'](?<src>[^\'"]+["\']))+[^>]*>(<\/iframe>)?#mi';
        $allVideoHtmlMatches = [];
        foreach ($contentKeys as $key) {
            preg_match_all(
                $videoHtmlRegex,
                $wikiPage[$key],
                $videoHtmlMatches
            );
            $allVideoHtmlMatches = array_merge_recursive(
                $allVideoWikiMatches,
                $videoHtmlMatches
            );
        }

        if (!empty($allVideoHtmlMatches['src'])) {
            // checker si l'url est une video youtube|vimeo|peertube
            // uploader
            echo 'TODO';
        }
        return $videos;
    }

    /**
     * All type of attachment related to a page or a bazar entry
     * UNUSED!!!
     *
     * @param string $remoteUrl distant url
     * @param array $wikiPage page or entry content as an array
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return void
     */
    public function downloadAttachments($remoteUrl, &$wikiPage, $overwrite = false, $peertubeSource = '')
    {
        // Handle Pictures and file attachments
        // Downloading images
        $images = $this->findDirectLinkAttachments($remoteUrl, $wikiPage, true);

        if (count($images)) {
            foreach ($images as $image) {
                $this->downloadDirectLinkAttachment($remoteUrl, $image, $overwrite);
            }
        }

        // Downloading hidden attachments
        $attachments = $this->findHiddenAttachments($remoteUrl, $wikiPage, true);

        if ($c = count($attachments)) {
            foreach ($attachments as $attachment) {
                $this->downloadHiddenAttachment($remoteUrl, $wikiPage['id_fiche'], $content['date_maj_fiche'], $attachment, $overwrite);
            }
        }

        // Handle Videos if a peertube location is configured
        if ($this->peertubeToken === null) {
            $this->initPeertubeToken();
        }
        if (!empty($this->peertubeToken)) {
            $videos = $this->findVideos($wikiPage, $peertubeSource, true);
            foreach ($videos as $video) {
                $this->importToPeertube($video['url'], $video['title']);
            }
        }
    }
}
