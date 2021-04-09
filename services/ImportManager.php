<?php

namespace YesWiki\Lms\Service;

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
            throw new \Exception(_('LMS_ERROR_NO_DATA'));
        } elseif (!$dataJson=json_decode($dataStr, true)) {
            throw new \Exception(_('LMS_ERROR_PARSING_DATA'), $dataStr);
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
                  'http'=>array(
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
                    throw new \Exception(_('LMS_ERROR_NO_PEERTUBE_TOKEN'));
                }
            } else {
                throw new \Exception(_('LMS_ERROR_NO_CREDENTIALS').': '.$apiUrl);
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
    private function importToPeertube($url, $title)
    {
        // Get channel id
        $channel = json_decode(file_get_contents($this->wiki->config['peertube_url'].'/api/v1/video-channels/'.$this->wiki->config['peertube_channel']), true);

        $data = [
          'channelId' => $channel['id'],
          'name' => $title,
          'targetUrl' => $url,
        ];
        $opts = array(
            'http'=>array(
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
            throw new \Exception($output._t('LMS_ERROR_DOWNLOADING').' '.$filename.': '.$err."\n"._t('LMS_REMOVING_CORRUPTED_FILE').' '.$filename);
        }
        return $output;
    }

    /**
     * Get images from html output of a page or bazar entry
     *
     * @param string $remoteUrl distant url
     * @param array $content page or entry content as an array
     * @return array images name
     */
    public function findImages($remoteUrl, $content)
    {
        preg_match_all(
            '#(?:href|src)="'.preg_quote($remoteUrl, '#').'files/(.*)"#Ui',
            $content['html_output'],
            $inlineImages
        );

        $bazarImages = array_filter($content, function ($k) {
            return str_starts_with($k, 'image');
        }, ARRAY_FILTER_USE_KEY);

        $images = array_merge($inlineImages[1], array_values($bazarImages));
        $images = array_unique($images);

        return $images;
    }

    /**
     * Generate distant image url and download to local image path
     *
     * @param string $remoteUrl distant image url
     * @param string $image local image path
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return void
     */
    public function downloadImage($remoteUrl, $image, $overwrite = false)
    {
        $remoteFileUrl = $remoteUrl.'/files/'.$image;
        $saveFileLoc = $this->getLocalFileUploadPath().'/'.$image;

        return $this->cURLDownload($remoteFileUrl, $saveFileLoc, $overwrite);
    }

    /**
     * Find file attachments in page or bazar entry
     *
     * @param string $remoteUrl distant url
     * @param array $content page or entry content as an array
     * @return array all file attachments
     */
    public function findFileAttachments($remoteUrl, $content)
    {
        preg_match_all(
            '#(?:href|src)="'.preg_quote($remoteUrl, '#').'\?.+/download&(?:amp;)?file=(.*)"#Ui',
            $content['html_output'],
            $htmlMatches
        );
        $wikiRegex = '#url="' . preg_quote($remoteUrl, '#')
                    . '(\?.+/download&(?:amp;)?file=(.*))"#Ui';
        preg_match_all(
            $wikiRegex,
            (!empty($content['bf_contenu']) ?
              $content['bf_contenu']
              : $content['bf_description'] ?? ''),
            $wikiMatches
        );

        $attachments = array_merge($htmlMatches[1], $wikiMatches[2]);
        $attachments = array_unique($attachments);

        return $attachments;
    }

    public function downloadAttachment($remoteUrl, $pageTag, $lastPageUpdate, $filename, $overwrite = false)
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
     * @param string $content html content to parse
     * @return array all videos
     */
    public function findVideos($content)
    {
      $videos = [];
      $videoWikiRegex = '#{{video(?:\s*(?:id="(?<id>\S+)"|serveur="(?<serveur>peertube|vimeo|youtube)"|peertubeinstance="(?<peertubeinstance>\S+)"|ratio="(?<ratio>.+)"|largeurmax="(?<largeurmax>\d+)"|hauteurmax="(?<hauteurmax>\d+)"| class="(?<class>.+)"))+\s*}}#i';
      preg_match_all(
          $videoWikiRegex,
          $content,
          $videoWikiMatches
      );

      if (!empty($videoWikiMatches['id'])) {
          foreach ($videoWikiMatches['id'] as $index => $videoId) {
              // trouver l'instance video entre youtube|vimeo|peertube
              // creer l'url de la video et la mettre dans $videos[$index]['url']
              if (empty($videoWikiMatches['serveur'][$index])) {
                  if (strlen($videoId) == 11) {
                      $videoWikiMatches['serveur'][$index] = 'youtube';
                  } elseif (preg_match("/^\d+$/", $videoId)) {
                      $videoWikiMatches['serveur'][$index] = 'vimeo';
                  } else {
                      $videoWikiMatches['serveur'][$index] = 'peertube';
                  }
              }
              switch ($videoWikiMatches['serveur'][$index]) {
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
                      if (!empty($videoWikiMatches['peertubeinstance'][$index])) {
                          $videos[$index]['url'] = $videoWikiMatches['peertubeinstance'][$index].'/videos/watch/'.$videoId;
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
                      throw new \Exception(_t('LMS_ERROR_PROVIDER').' "'.$videoWikiMatches[0][$index].'".');
              }
          }
      }

      $videoHtmlRegex = '#<iframe.+?(?:\s*width=["\'](?<width>[^"\']+)["\']|\s*height=["\'](?<height>[^\'"]+)["\']|\s*src=["\'](?<src>[^\'"]+["\']))+[^>]*>(<\/iframe>)?#mi';
      preg_match_all(
          $videoHtmlRegex,
          $content,
          $videoHtmlMatches
      );

      if (!empty($videoHtmlMatches['src'])) {
          // checker si l'url est une video youtube|vimeo|peertube
          // uploader
          echo 'TODO';
      }
      return $videos;
    }

    /**
     * All type of attachment related to a page or a bazar entry
     *
     * @param string $remoteUrl distant url
     * @param array $content page or entry content as an array
     * @param boolean $overwrite overwrite existing file ? (default:false)
     * @return void
     */
    public function downloadAttachments($remoteUrl, &$content, $overwrite = false, $peertubeSource = '')
    {
        // Handle Pictures and file attachments
        // Downloading images
        $images = $this->findImages($remoteUrl, $content);

        if (count($images)) {
            foreach ($images as $image) {
              $this->downloadImage($remoteUrl, $image, $overwrite);
            }
        }

        // Downloading other attachments
        $attachments = $this->findFileAttachments($remoteUrl, $content);

        if ($c = count($attachments)) {
            foreach ($attachments as $attachment) {
              $this->downloadAttachment($remoteUrl, $content['id_fiche'], $content['date_maj_fiche'], $attachment, $overwrite);
            }
        }

        // TODO : generic search on all textelong fields

        $replaced = preg_replace(
            $wikiRegex,
            'url="'.$this->wiki->getBaseUrl().'/$1"',
            (!empty($content['bf_contenu']) ?
              $content['bf_contenu']
              : $content['bf_description'] ?? ''),
        );
        if (!empty($content['bf_contenu'])) {
            $content['bf_contenu'] = $replaced;
        } elseif (!empty($content['bf_description'])) {
            $content['bf_description'] = $replaced;
        }

        // Handle Videos if a peertube location is configured
        if ($this->peertubeToken === null) {
            $this->initPeertubeToken();
        }
        if (!empty($this->peertubeToken)) {
            $content = (!empty($content['bf_contenu']) ?
              $content['bf_contenu']
              : $content['bf_description'] ?? '');
            $videos = $this->findVideos($content);
            foreach ($videos as $video) {
                $this->importToPeertube($video['url'], $video['title']);
            }
        }
    }
}
