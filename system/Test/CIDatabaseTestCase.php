<?php namespace CodeIgniter\Test;

use CodeIgniter\ConfigException;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\MigrationRunner;
use Config\Services;

class CIDatabaseTestCase extends CIUnitTestCase
{
	/**
	 * Should the db be refreshed before
	 * each test?
	 *
	 * @var bool
	 */
	protected $refresh = true;

	/**
	 * The name of the fixture used for all tests
	 * within this test case.
	 *
	 * @var string
	 */
	protected $seed = '';

	/**
	 * The path to where we can find the migrations
	 * and seeds directories. Allows overriding
	 * the default application directories.
	 *
	 * @var string
	 */
	protected $basePath = APPPATH.'../tests/_support/_database';

	/**
	 * The name of the database group to connect to.
	 * If not present, will use the defaultGroup.
	 *
	 * @var string
	 */
	protected $DBGroup = 'tests';

	/**
	 * Our database connection.
	 * 
	 * @var BaseConnection
	 */
	protected $db;

	/**
	 * Migration Runner instance.
	 * 
	 * @var MigrationRunner|mixed
	 */
	protected $migrations;

	/**
	 * Seeder instance 
	 *
	 * @var \CodeIgniter\Database\Seeder
	 */
	protected $seeder;

	/**
	 * Stores information needed to remove any
	 * rows inserted via $this->hasInDatabase();
	 *
	 * @var array
	 */
	protected $insertCache = [];

	//--------------------------------------------------------------------

	public function __construct()
	{
	    parent::__construct();
		
		$this->db = \Config\Database::connect($this->DBGroup);
		$this->db->initialize();

		// Ensure that we can run migrations
		$config = new \Config\Migrations();
		$config->enabled = true;

		$this->migrations = Services::migrations($config, $this->db);
		$this->migrations->setSilent(true);

		$this->seeder = \Config\Database::seeder($this->DBGroup);
		$this->seeder->setSilent(true);
	}
	
	//--------------------------------------------------------------------

	/**
	 * Ensures that the database is cleaned up to a known state
	 * before each test runs.
	 *
	 * @throws ConfigException
	 */
	public function setUp()
	{
		if ($this->refresh === true)
		{
			if (! empty($this->basePath))
			{
				$this->migrations->setPath(rtrim($this->basePath, '/').'/migrations');
			}

			$this->migrations->version(0);
			$this->migrations->latest();
		}

		if (! empty($this->seed))
		{
			if (! empty($this->basePath))
			{
				$this->seeder->setPath(rtrim($this->basePath, '/').'/seeds');
			}
			
			$this->seed($this->seed);
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Takes care of any required cleanup after the test, like
	 * removing any rows inserted via $this->hasInDatabase()
	 */
	public function tearDown()
	{
	    if (! empty($this->insertCache))
	    {
		    foreach ($this->insertCache as $row)
		    {
			    $this->db->table($row[0])
				         ->where($row[1])
				         ->delete();
		    }
	    }
	}

	//--------------------------------------------------------------------


	/**
	 * Seeds that database with a specific seeder.
	 *
	 * @param string $name
	 */
	public function seed(string $name)
	{
        return $this->seeder->call($name);
	}

	//--------------------------------------------------------------------

	//--------------------------------------------------------------------
	// Database Test Helpers
	//--------------------------------------------------------------------

	/**
	 * Asserts that records that match the conditions in $where do
	 * not exist in the database.
	 *
	 * @param string $table
	 * @param array  $where
	 *
	 * @return bool
	 */
	public function dontSeeInDatabase(string $table, array $where)
	{
		$count = $this->db->table($table)
				        ->where($where)
				        ->countAllResults();

	    $this->assertTrue($count == 0, 'Row was found in database');
	}
	
	//--------------------------------------------------------------------

	/**
	 * Asserts that records that match the conditions in $where DO
	 * exist in the database.
	 * 
	 * @param string $table
	 * @param array  $where
	 *
	 * @return bool
	 * @throws \CodeIgniter\DatabaseException
	 */
	public function seeInDatabase(string $table, array $where)
	{
		$count = $this->db->table($table)
		                  ->where($where)
		                  ->countAllResults();

		$this->assertTrue($count > 0, 'Row not found in database');
	}

	//--------------------------------------------------------------------

	/**
	 * Fetches a single column from a database row with criteria
	 * matching $where.
	 *
	 * @param string $table
	 * @param string $column
	 * @param array  $where
	 *
	 * @return bool
	 * @throws \CodeIgniter\DatabaseException
	 */
	public function grabFromDatabase(string $table, string $column, array $where)
	{
	    $query = $this->db->table($table)
		                  ->select($column)
		                  ->where($where)
		                  ->get();

		$query = $query->getRow();

		return $query->$column ?? false;
	}
	
	//--------------------------------------------------------------------

	/**
	 * Inserts a row into to the database. This row will be removed
	 * after the test has run.
	 *
	 * @param string $table
	 * @param array  $data
	 *
	 * @throws \CodeIgniter\DatabaseException
	 */
	public function hasInDatabase(string $table, array $data)
	{
		$this->insertCache[] = [
			$table, $data
		];

	    $this->db->table($table)
		         ->insert($data);
	}

	//--------------------------------------------------------------------

	/**
	 * Asserts that the number of rows in the database that match $where
	 * is equal to $expected.
	 *
	 * @param int    $expected
	 * @param string $table
	 * @param array  $where
	 *
	 * @return bool
	 * @throws \CodeIgniter\DatabaseException
	 */
	public function seeNumRecords(int $expected, string $table, array $where)
	{
	    $count = $this->db->table($table)
		                  ->where($where)
		                  ->countAllResults();

		$this->assertEquals($count, $expected, 'Wrong number of matching rows in database.');
	}
	
	//--------------------------------------------------------------------
	
	
}