# ImageGallery schema

`ImageGallerySchema` describes a page or component containing a collection of images.

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ImageGallerySchema;

$gallerySchema = ImageGallerySchema::create(
    'gallery-' . $page->ID,
    $page->Title,
    $page->MetaDescription
);

foreach ($page->GalleryImages() as $image) {
    $gallerySchema->addImage(
        $image->getAbsoluteURL(),
        $image->Title,
        null,
        $image->getWidth(),
        $image->getHeight()
    );
}

SchemaRegistry::add($gallerySchema);
```

The gallery has no automatic WebPage relationship. Use `setIsPartOfPage($pageURL)` when appropriate. For a dedicated gallery page, call `$gallerySchema->setMainEntityOfPage($page->AbsoluteLink())`. Individual images are `ImageObject` entries under `hasPart`, using `contentUrl` for the actual image asset. Name, description, width and height are optional.

A representative thumbnail can be set with:

```php
$gallerySchema->setThumbnail($thumbnailURL);
```

Pages containing multiple galleries can give each gallery a stable identifier using the optional fourth argument:

```php
$gallerySchema = ImageGallerySchema::create(
    'gallery-' . $gallery->ID,
    $gallery->Title
);
```

This produces a site-scoped entity ID ending in `#imagegallery-gallery-42`.

The builder also inherits `Schema::update()` for additional Schema.org properties.
