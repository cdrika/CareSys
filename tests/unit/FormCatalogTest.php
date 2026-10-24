<?php
use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\{FormCatalog,GeographyCatalog};

final class FormCatalogTest extends CIUnitTestCase
{
    public function testStandardChoicesDoNotConflateJobsWithAccessRoles():void
    {
        $this->assertContains('Caregiver',FormCatalog::choices('profile','role')[1]);
        $this->assertNull(FormCatalog::choices('member','role'));
        $this->assertContains('First aid',FormCatalog::QUALIFICATIONS);
        $this->assertContains('Home care',FormCatalog::CARE_SETTINGS);
        $this->assertContains('Fijian',FormCatalog::LANGUAGES);
        [$text,$example]=FormCatalog::help('profile','care_settings','Care settings');$this->assertStringContainsString('place',$text);$this->assertStringContainsString('Home care',$example);
    }
    public function testLocationsUseNamesAndEnforceTheParentHierarchy():void
    {
        $this->assertGreaterThan(200,count(GeographyCatalog::countries()));
        $states=GeographyCatalog::items('states','US');$this->assertContains('California',array_column($states,'name'));
        $counties=GeographyCatalog::items('counties','US','US-CA');$this->assertContains('Sacramento County',array_column($counties,'name'));
        $cities=GeographyCatalog::items('cities','US','US-CA','US-CA-067');$this->assertContains('Sacramento',array_column($cities,'name'));
        $this->assertNotContains('Davis',array_column($cities,'name'));
        $this->assertSame([],GeographyCatalog::items('cities','US','US-NY','US-CA-067'));
        $this->assertSame([],GeographyCatalog::country('../'));
        $this->assertSame('US-CA-067',GeographyCatalog::resolve('US-CA-SAC')['code']);
        $this->assertStringContainsString('California',GeographyCatalog::label('US-CA-SAC'));
        $city=GeographyCatalog::resolve('US-CA-067-C5389489');$this->assertSame('Sacramento',$city['name']);$this->assertSame('US-CA-067',$city['county']);
    }
}
