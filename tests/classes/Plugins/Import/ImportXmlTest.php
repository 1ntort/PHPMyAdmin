<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;

#[CoversClass(ImportXml::class)]
#[RequiresPhpExtension('xml')]
#[RequiresPhpExtension('xmlwriter')]
class ImportXmlTest extends AbstractTestCase
{
    protected ImportXml $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $GLOBALS['error'] = null;
        $GLOBALS['timeout_passed'] = null;
        $GLOBALS['maximum_time'] = null;
        ImportSettings::$charsetConversion = false;
        Current::$database = '';
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['sql_query_disabled'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        ImportSettings::$goSql = false;

        $this->object = new ImportXml();

        //setting
        $GLOBALS['finished'] = false;
        ImportSettings::$readLimit = 100000000;
        $GLOBALS['offset'] = 0;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $GLOBALS['import_file'] = 'tests/test_data/phpmyadmin_importXML_For_Testing.xml';
        $GLOBALS['import_text'] = 'ImportXml_Test';
        $GLOBALS['compression'] = 'none';
        ImportSettings::$readMultiply = 10;
        $GLOBALS['import_type'] = 'Xml';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Test for getProperties
     */
    #[Group('medium')]
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        self::assertEquals(
            __('XML'),
            $properties->getText(),
        );
        self::assertEquals(
            'xml',
            $properties->getExtension(),
        );
        self::assertEquals(
            'text/xml',
            $properties->getMimeType(),
        );
        self::assertNull($properties->getOptions());
        self::assertEquals(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport
     */
    #[Group('medium')]
    #[RequiresPhpExtension('simplexml')]
    public function testDoImport(): void
    {
        //$import_notice will show the import detail result

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        // If import successfully, PMA will show all databases and tables
        // imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           phpmyadmintest (Options)
           pma_bookmarktest (Structure) (Options)
        */

        //asset that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $GLOBALS['import_notice'],
        );
        self::assertStringContainsString('Go to database: `phpmyadmintest`', $GLOBALS['import_notice']);
        self::assertStringContainsString('Edit settings for `phpmyadmintest`', $GLOBALS['import_notice']);
        self::assertStringContainsString('Go to table: `pma_bookmarktest`', $GLOBALS['import_notice']);
        self::assertStringContainsString('Edit settings for `pma_bookmarktest`', $GLOBALS['import_notice']);
        self::assertTrue($GLOBALS['finished']);
    }
}
