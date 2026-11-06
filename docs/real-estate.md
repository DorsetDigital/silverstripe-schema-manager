# Property listings and real estate

Property schema is **opt-in** and does not assume a property model or URL strategy. Integrate it into the website's own Silverstripe page/controller extension and map the property's actual DataObject fields. The module provides four typed builders:

- `PropertyCollectionSchema` — `CollectionPage` using the current page's existing stable `#webpage` ID.
- `RealEstateListingSchema` — `RealEstateListing` using the detail page's existing stable `#webpage` ID.
- `PropertySchema` — the physical `Accommodation`, `House`, `SingleFamilyResidence`, `Apartment`, `Residence`, `Suite`, `Room` or `HotelRoom`.
- `RealEstateOfferSchema` — sale or rental `Offer`, referencing the property entity by `@id`.

Use `ItemListSchema` on collection pages (one item per visible result), rather than repeating full property definitions in search results.

## Detail page example

The following example illustrates a project extension attached to the actual property detail page type (a `SiteTree` page). Adapt the example to your property's fields and relationships:

```php
use DorsetDigital\SchemaManager\Control\SchemaIDs;
use DorsetDigital\SchemaManager\Model\Schema\PropertySchema;
use DorsetDigital\SchemaManager\Model\Schema\RealEstateListingSchema;
use DorsetDigital\SchemaManager\Model\Schema\RealEstateOfferSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;
use SilverStripe\Core\Extension;

class PropertyDetailSchemaExtension extends Extension
{
    public function updateSchemaManagerEntities(array &$entities): void
    {
        $page = $this->owner;
        $url = $page->AbsoluteLink();
        $key = (string) $page->ID;

        // Replace the automatically built WebPage, preserving its canonical ID,
        // metadata, breadcrumbs, and site graph relationships.
        foreach ($entities as $index => $entity) {
            if (!$entity instanceof WebPageSchema) {
                continue;
            }

            $property = PropertySchema::create($key, $page->Title, 'House')
                ->setURL($url)
                ->setBedrooms(3)
                ->setAddress(['addressLocality' => 'Oxford', 'addressCountry' => 'GB']);

            $offer = RealEstateOfferSchema::create($key, $property->getEntityID())
                ->setURL($url)
                ->setPrice(625000, 'GBP')
                ->setBusinessFunction('Sell')
                ->setSeller(SchemaIDs::organisation());

            $entities[$index] = RealEstateListingSchema::fromWebPage($entity)
                ->setOffer($offer->getEntityID());

            $entities[] = $property;
            $entities[] = $offer;
            break;
        }
    }
}
```

Configure this extension on **your property detail page type**, not globally. For property detail routes implemented with a controller that isn't itself a `SiteTree` page, use `SchemaRegistry::add()` after the controller has identified the requested property; keep the page and property URLs canonical and give the page one stable `#webpage` entity.

The example values are illustrative. In production, map actual bedroom counts, area, address, images, price and dates, and omit unavailable values. Do not invent property data.

### Rental offers

Use `setRentalPrice(1450, 'GBP', 'MON')` for monthly rental price. This sets the lease business function and a `UnitPriceSpecification`. Sale offers use `setPrice(625000, 'GBP')->setBusinessFunction('Sell')`. Neither automatically maps estate-agency states such as SSTC or Under Offer to ecommerce availability.

## Collection page example

On a property results page, use the existing `ItemListSchema` with the properties shown on **that page**, and replace its `WebPageSchema` with a `PropertyCollectionSchema`:

```php
use DorsetDigital\SchemaManager\Model\Schema\ItemListSchema;
use DorsetDigital\SchemaManager\Model\Schema\PropertyCollectionSchema;
use DorsetDigital\SchemaManager\Model\Schema\WebPageSchema;

// Inside updateSchemaManagerEntities(array &$entities) on the results page:
$list = ItemListSchema::create('properties-' . $this->owner->ID, 'Properties for sale');
foreach ($this->owner->getVisibleProperties() as $property) {
    $list->addItem($property->AbsoluteLink(), $property->Title);
}
foreach ($entities as $index => $entity) {
    if ($entity instanceof WebPageSchema) {
        $entities[$index] = PropertyCollectionSchema::fromWebPage($entity, $list->getEntityID());
        $entities[] = $list;
        break;
    }
}
```

For paginated or filtered results, make the list's identifier unique to the canonical results URL or filter/page combination and include only currently displayed properties. Use `ItemListSchema::addEntity()` if a stable property schema entity is intentionally referenced; `addItem()` suffices for canonical detail links.

## Stable IDs and schema relationships

- `RealEstateListing` and `CollectionPage` are page types and **replace**, rather than duplicate, the existing `WebPage` in the graph.
- Physical properties use `#property-<identifier>` and offers use `#property-offer-<identifier>` on the site's base URL, via `SchemaIDs::forEntity()`. Supply a stable, URL-safe identifier (slug or immutable key), not the public URL as the identifier.
- An offer's `itemOffered` references the physical property. The detail page's `mainEntity` references the offer.
- Site-wide `Organization` can be referenced through `SchemaIDs::organisation()` rather than duplicated.
- These builders emit Schema.org markup; they do not guarantee Google rich results.

You can use inherited `update(array $data)` for less-common Schema.org attributes as needed.
