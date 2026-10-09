<?php

namespace DorsetDigital\SchemaManager\Model\Schema;

class PersonSchema extends Schema
{
    public static function create(string $id, string $name): static
    {
        return new static(['@type' => 'Person', '@id' => $id, 'name' => $name]);
    }

    public function setJobTitle(?string $title): static { if ($title) $this->data['jobTitle'] = $title; return $this; }
    public function setDescription(?string $description): static { if ($description) $this->data['description'] = $description; return $this; }
    public function setImage(?string $url): static { if ($url) $this->data['image'] = $url; return $this; }
    public function setURL(?string $url): static { if ($url) $this->data['url'] = $url; return $this; }
    public function setWorksFor(string $id): static { $this->data['worksFor'] = ['@id' => $id]; return $this; }
    public function setSameAs(array $urls): static { $this->data['sameAs'] = array_values(array_filter($urls)); return $this; }
    public function setEmail(?string $email): static { if ($email) $this->data['email'] = $email; return $this; }
    public function setTelephone(?string $telephone): static { if ($telephone) $this->data['telephone'] = $telephone; return $this; }
    public function setCredentials(array $credentials): static { $this->data['hasCredential'] = $credentials; return $this; }
}
