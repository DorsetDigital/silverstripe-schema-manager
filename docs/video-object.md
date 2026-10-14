# VideoObject schema

Use `VideoObjectSchema` to describe a video embedded in a page, including YouTube and Vimeo videos. The builder can be called from a Silverstripe content element and **does not require a CMS page annotation** or any video-provider API.

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\VideoObjectSchema;

$videoID = $this->VideoID;
$pageURL = $this->getPage()->AbsoluteLink();

SchemaRegistry::add(
    VideoObjectSchema::create('youtube-' . $videoID, $this->VideoTitle, $this->VideoDescription)
        ->setURL('https://www.youtube.com/watch?v=' . rawurlencode($videoID))
        ->setEmbedURL('https://www.youtube.com/embed/' . rawurlencode($videoID))
        ->setIsPartOfPage($pageURL)
);
```

Use a stable, video-specific identifier rather than an element ID when the same video may appear in several elements. For example, `youtube-VIDEO_ID` and `vimeo-VIDEO_ID` distinguish videos from different providers. If you intentionally need distinct entities for each element, use a stable element-specific identifier instead.

## YouTube and Vimeo URLs

A video ID is enough to construct the playback URL, but **not enough to populate every useful VideoObject property**.

| Provider | Original video URL (`setURL()`) | Embeddable player URL (`setEmbedURL()`) |
| --- | --- | --- |
| YouTube | `https://www.youtube.com/watch?v=VIDEO_ID` | `https://www.youtube.com/embed/VIDEO_ID` |
| Vimeo | `https://vimeo.com/VIDEO_ID` | `https://player.vimeo.com/video/VIDEO_ID` |

For Vimeo, your existing `https://player.vimeo.com/video/$VideoID` URL is already suitable for `embedUrl`:

```php
$videoID = $this->VideoID;

$schema = VideoObjectSchema::create('vimeo-' . $videoID, $this->VideoTitle)
    ->setURL('https://vimeo.com/' . rawurlencode($videoID))
    ->setEmbedURL('https://player.vimeo.com/video/' . rawurlencode($videoID))
    ->setIsPartOfPage($this->getPage()->AbsoluteLink());

SchemaRegistry::add($schema);
```

For unlisted/private videos, the actual player URL may include additional parameters or tokens. Use the real embed URL where necessary rather than assuming the simple pattern works for every video.

## Optional metadata

For better structured-data completeness, add the video thumbnail and upload date when you have reliable values:

```php
$schema
    ->setThumbnailURL($thumbnailURL)
    ->setUploadDate('2026-10-01')
    ->setDuration('PT3M25S');
```

- `thumbnailUrl` should be a working, publicly accessible image URL. Do not assume YouTube's maximum-resolution thumbnail exists for every video.
- `uploadDate` is the video's actual publication date, not the date your Silverstripe element was created.
- `duration` uses ISO 8601 duration format, such as `PT3M25S`.
- `contentUrl` is for a directly accessible video file. **Do not use a YouTube or Vimeo watch/player URL as `contentUrl`.**

The builder does not fetch metadata from YouTube or Vimeo. You can supply it from fields on your element or another integration. A URL-only VideoObject is valid as a schema entity but may not meet Google's requirements for video rich results; in particular, Google requires video name, thumbnail and upload date for video indexing features.

For an embedded video within a text/video element, `setIsPartOfPage($pageURL)` is normally appropriate. Reserve `setMainEntityOfPage($pageURL)` for a page primarily dedicated to that video.

## Public builder methods

These are the methods declared by `VideoObjectSchema`. It also inherits the [shared Schema methods](developer-api.md#shared-schema-builder-methods).

| Method | Purpose |
| --- | --- |
| `create(string $identifier, string $name, ?string $description = null)` | Create a VideoObject with a stable, site-scoped ID. |
| `setURL(?string $url)` | Set or clear the video's public watch-page URL. |
| `setEmbedURL(?string $url)` | Set or clear the embeddable player URL. |
| `setContentURL(?string $url)` | Set or clear a direct video file URL. |
| `setThumbnailURL(?string $url)` | Set or clear the thumbnail image URL. |
| `setUploadDate(?string $date)` | Set or clear the video publication date. |
| `setDuration(?string $duration)` | Set or clear the ISO 8601 duration. |

Each optional setter accepts `null` to remove the corresponding property. The inherited `update(array $data)` method can add further Schema.org VideoObject properties when required.
