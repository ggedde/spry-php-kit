<?php declare(strict_types=1);
/**
 * Global Functions file
 */

namespace SpryPhp\Provider;

use Exception;

/**
 * Commands Class
 * Provides general commands for the CLI
 */
class Commands
{
    /**
     * Run a Command
     *
     * @throws Exception
     *
     * @return never
     */
    public static function run(): never
    {
        $command = self::getCommand();

        if (!$command) {
            throw new Exception("\n\e[91mSpryPhp: Missing Command. Try spryphp help");
        }

        if (!method_exists(__CLASS__, $command)) {
            throw new Exception("\n\e[91mSpryPhp: Command is not Valid. Try spryphp help");
        }

        if ($command === 'showHelp') {
            self::showHelp();
            exit;
        }

        $argv = self::getArgs();
        $value = isset($argv[2]) ? Functions::formatCamelCase($argv[2]) : null;

        $options = (object) [];

        if (in_array('-db', array_map('strtolower', $argv), true) || in_array('--database', array_map('strtolower', $argv), true)) {
            $options->database = true;
        }

        if (in_array('-f', array_map('strtolower', $argv), true) || in_array('--force', array_map('strtolower', $argv), true)) {
            $options->force = true;
        }

        if ($command === 'updateDbSchema') {
            self::updateDbSchema($options);
            exit;
        }

        self::$command($value, $options);
        exit;
    }

    /**
     * Get Command
     *
     * @return string|null
     */
    private static function getCommand(): ?string
    {
        $commands = [
            'model' => 'makeModel',
            'm' => 'makeModel',
            'controller' => 'makeController',
            'c' => 'makeController',
            'cm' => 'makeControllerAndModel',
            'mc' => 'makeControllerAndModel',
            'view' => 'makeView',
            'v' => 'makeView',
            'provider' => 'makeProvider',
            'p' => 'makeProvider',
            'type' => 'makeType',
            't' => 'makeType',
            'update' => 'updateDbSchema',
            'u' => 'updateDbSchema',
            'help' => 'showHelp',
            'h' => 'showHelp',
        ];

        $argv = self::getArgs();

        return isset($argv[1]) && !empty($commands[trim($argv[1])]) ? trim($commands[trim($argv[1])]) : null;
    }

    /**
     * Get ArgVars
     *
     * @return array<int,string>
     */
    private static function getArgs(): array
    {
        $argv = [];
        if (!empty($_SERVER['argv']) && is_array($_SERVER['argv'])) {
            foreach ($_SERVER['argv'] as $value) {
                $argv[] = is_string($value) || is_int($value) || is_float($value) ? trim(strval($value)) : '';
            }
        }

        return $argv;
    }

    /**
     * Make a Controller And Model
     *
     * @param string    $name    Name of Controller and Model. Controller Plural Name and Model gets Singular Name.
     * @param \stdClass $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeControllerAndModel(string $name, object $options): void
    {
        self::makeModel($name, $options);

        $options->makeModel = true;
        self::makeController($name, $options);
    }

    /**
     * Make a Controller
     *
     * @param string $name    Name of Controller. Gets Plural Name.
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeController(string $name, object $options): void
    {
        $controllerName = ucwords(Functions::formatCamelCase(Functions::formatPlural($name)));

        if (!is_dir(Functions::constantString('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Controller')) {
            mkdir(Functions::constantString('APP_PATH_APP').'/Controller');
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Controller')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Controller Directory");
        }

        if (file_exists(Functions::constantString('APP_PATH_APP').'/Controller/'.$controllerName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Controller already exists");
        }

        $database = !empty($options->database);
        $modelName = '';
        if (!empty($options->makeModel)) {
            $modelName = ucwords(Functions::formatCamelCase(Functions::formatSingular($controllerName)));
        }

        $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle The '.$controllerName.' Controller
 */

namespace App\Controller;

'.($modelName ? 'use App\Model\\'.$modelName.';
' : '').($database ? 'use SpryPhp\Provider\Db;
' : '').'
/**
 * '.$controllerName.' Controller
 */
class '.$controllerName.'
{'.($database ? '
    /**
	 * Database Table
	 *
	 * @var string $dbTable
	 */
	private static $dbTable = \''.strtolower($controllerName).'\';' : '').'

	/**
	 * Get '.$controllerName.($database ? ' or single '.($modelName ? $modelName.' ' : '').'if WHERE has id.' : '').($database ? '
	 *
	 * @param array $columns' : '
     *').'
	 * @param array $where'.($database ? '
	 * @param array $order
	 * @param array $limit' : '').'
	 *
	 * @return '.($modelName ? $modelName.'|'.$modelName.'[]' : 'array').'
	 */
	public static function get('.($database ? 'array $columns = [\'*\'], ' : '').'array $where = []'.($database ? ', array $order = [], array $limit = []' : '').'): '.($modelName ? $modelName : 'object').'|array
	{
		if (!empty($where[\'id\'])) {
            $item = '.($modelName ? 'new '.$modelName.'($where[\'id\'])' : ($database ? 'Db::get(self::$dbTable, null, $where)' : '(object) [\'id\' => \'\']')).';
			if ($item) {
				return $item;
			}
		}

		$items = [];'.($database ? '
		foreach (Db::select(self::$dbTable, $columns, null, $where, [], $order, $limit) as $item) {
			$items[] = '.($modelName ? 'new '.$modelName.'($item)' : '$item').';
		}' : '').'

		return $items;
	}'.($database ? '

    /**
	 * Add '.$controllerName.'
	 *
	 * @param string $name
	 *
	 * @return '.$modelName.'|null
	 */
	public static function add(string $name): ?'.($modelName ? $modelName : 'object').'
	{
		$item = '.($modelName ? 'new '.$modelName.'(
			(object) [
				\'name\' => $name,
			]
		)' : '(object) [
			\'name\' => $name,
		]').';

        $insert = '.($modelName ? '$item->insert()' : 'Db::insert(self::$dbTable, $item)').' ? true : null;

		return $insert;
	}' : '').($database ? '

    /**
	 * Delete Single '.$controllerName.' by ID
	 *
	 * @param string $id
	 *
	 * @return bool|null
	 */
	public static function delete(string $id): ?bool
	{
        $delete = '.($modelName ? '(new '.$modelName.'($id))->delete()' : 'Db::delete(self::$dbTable, [\'id\' => $id])').' ? true : null;

		return $delete;
	}' : '').'
}
';

        file_put_contents(Functions::constantString('APP_PATH_APP').'/Controller/'.$controllerName.'.php', $contents);
        echo "\n\e[92mController Created Successfully!\n";
    }

    /**
     * Make a Model
     *
     * @param string $name    Name of Model. Gets Singular Name.
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeModel(string $name, object $options): void
    {
        $modelName = ucwords(Functions::formatCamelCase(Functions::formatSingular($name)));

        if (!is_dir(Functions::constantString('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Model')) {
            mkdir(Functions::constantString('APP_PATH_APP').'/Model');
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Model')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Model Directory");
        }

        if (file_exists(Functions::constantString('APP_PATH_APP').'/Model/'.$modelName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Model already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle The '.$modelName.' Class
 */

namespace App\Model;'.(!empty($options->database) ? '

use SpryPhp\Model\DbModel;
' : '
').'
/**
 * '.$modelName.' Instance
 */
class '.$modelName.(!empty($options->database) ? ' extends DbModel' : '').'
{
	/**
	 * '.$modelName.' Name
	 *
	 * @var string $name
	 */
	public string $name = \'\';
'.(!empty($options->database) ? '
    /**
	 * Database Table
	 *
	 * @var string $dbTable
	 */
	protected string $dbTable = \''.strtolower(Functions::formatPlural($modelName)).'\';
' : '').'
	/**
     * Construct the '.$modelName.'
     '.(!empty($options->database) ? '* Either pass data as object or UUID.
     *' : '*').'
     * @param object|string $obj - Data Object'.(!empty($options->database) ? ' or UUID as string' : '').'
     */
    public function __construct(object|string $obj)
    {'.(!empty($options->database) ? '
        parent::__construct($obj);' : '
        // Do Something').'
    }
}
';

        file_put_contents(Functions::constantString('APP_PATH_APP').'/Model/'.$modelName.'.php', $contents);
        echo "\n\e[92mModel Created Successfully!\n";
    }

    /**
     * Make a Model
     *
     * @param string $name    Name of Model. Gets Singular Name.
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeView(string $name, object $options): void
    {


        $viewPathName = ucwords(Functions::formatCamelCase($name));
        $viewPathName = str_replace(' ', '/', ucwords(str_replace('/', ' ', $viewPathName)));

        $viewBaseName = ucwords(Functions::formatCamelCase(basename($name)));

        $viewParentName = trim(str_replace($viewBaseName, '', $viewPathName), '/');

        if (!is_dir(Functions::constantString('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/View')) {
            mkdir(Functions::constantString('APP_PATH_APP').'/View');
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/View')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create View Directory");
        }

        $paths = explode('/', $viewPathName);
        if (count($paths) > 1) {
            $fullViewPath = '';
            foreach ($paths as $pathIndex => $path) {
                if ($pathIndex < (count($paths) - 1)) {
                    $fullViewPath .= '/'.ucwords($path);
                    if (!is_dir(Functions::constantString('APP_PATH_APP').'/View'.$fullViewPath)) {
                        mkdir(Functions::constantString('APP_PATH_APP').'/View'.$fullViewPath);
                    }

                    if (!is_dir(Functions::constantString('APP_PATH_APP').'/View'.$fullViewPath)) {
                        throw new Exception("\n\e[91mSpryPhp: Could Not Create View Directory");
                    }
                }
            }
        }

        if (file_exists(Functions::constantString('APP_PATH_APP').'/View/'.$viewPathName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: View already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * '.$viewBaseName.' View
 */

namespace App\View'.($viewParentName ? '\\'.str_replace('/', '\\', $viewParentName) : '').';

use SpryPhp\Model\View;
use SpryPhp\Model\PageMeta;

/**
 * Class for '.$viewBaseName.' View
 */
class '.$viewBaseName.' extends View
{
    /**
     * Set the Page Meta
     *
     * @return PageMeta
     */
    public function meta(): PageMeta
    {
        return new PageMeta(
            title:       \''.$viewBaseName.'\',
            description: \'Welcome to '.$viewBaseName.'\',
        );
    }

    /**
     * Render the '.$viewBaseName.' View
     */
    public function render(): void
    {
        ?>
            Hello World!
        <?php
    }
}
';

        file_put_contents(Functions::constantString('APP_PATH_APP').'/View/'.$viewPathName.'.php', $contents);
        echo "\n\e[92mView Created Successfully!\n";
    }

    /**
     * Make a Type
     *
     * @param string $name    Name of Type.
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeType(string $name, object $options): void
    {
        $typeName = ucwords(Functions::formatCamelCase($name));

        if (!is_dir(Functions::constantString('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Type')) {
            mkdir(Functions::constantString('APP_PATH_APP').'/Type');
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Type')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Type Directory");
        }

        if (file_exists(Functions::constantString('APP_PATH_APP').'/Type/'.$typeName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Type already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle '.$typeName.' Types
 */

namespace App\Type;

/**
 * '.$typeName.' Types
 */
enum '.$typeName.': string
{
    case TypeA = \'type-a\';
    case TypeB = \'type-b\';
}
';

        file_put_contents(Functions::constantString('APP_PATH_APP').'/Type/'.$typeName.'.php', $contents);
        echo "\n\e[92mType Created Successfully!\n";
    }

    /**
     * Make a Provider
     *
     * @param string $name    Name of Provider.
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function makeProvider(string $name, object $options): void
    {
        $providerName = ucwords(Functions::formatCamelCase($name));

        if (!is_dir(Functions::constantString('APP_PATH_APP'))) {
            throw new Exception("\n\e[91mSpryPhp: Can't find App Directory");
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Provider')) {
            mkdir(Functions::constantString('APP_PATH_APP').'/Provider');
        }

        if (!is_dir(Functions::constantString('APP_PATH_APP').'/Provider')) {
            throw new Exception("\n\e[91mSpryPhp: Could Not Create Provider Directory");
        }

        if (file_exists(Functions::constantString('APP_PATH_APP').'/Provider/'.$providerName.'.php')) {
            throw new Exception("\n\e[91mSpryPhp: Provider already exists");
        }

        $contents = '<?php declare(strict_types=1);
/**
 * This file is to handle the '.$providerName.' Provider
 */

namespace App\Provider;

/**
 * '.$providerName.' Provider Class
 */
class '.$providerName.'
{
    /**
     * Run a Function
     *
     * @return void
     */
    public static function func(): void
    {

    }
}
';

        file_put_contents(Functions::constantString('APP_PATH_APP').'/Provider/'.$providerName.'.php', $contents);
        echo "\n\e[92mProvider Created Successfully!\n";
    }

    /**
     * Returns the Plural version of the string.
     *
     * @param object $options Additional Options.
     *
     * @throws Exception
     *
     * @return void
     */
    private static function updateDbSchema(object $options): void
    {
        if (!defined('APP_PATH_DB_SCHEMA_FILE') || empty(Functions::constantString('APP_PATH_DB_SCHEMA_FILE'))) {
            throw new Exception("\n\e[91mSpryPhp: Constant APP_PATH_DB_SCHEMA_FILE does not exist.");
        }

        $changes = Db::updateSchema(Functions::constantString('APP_PATH_DB_SCHEMA_FILE'), !empty($options->force));
        if ($changes) {
            echo "\n";
            foreach ($changes as $change) {
                echo $change."\n";
            }
            echo "\n\e[92mDatabase Schema Updated Successfully!\n";

            return;
        }

        echo "\n\e[92mDatabase Schema is up to date!\n";
    }

    /**
     * Show Help
     *
     * @return void
     */
    private static function showHelp(): void
    {
        echo "\nSpryPhp Commands:\n\n\t
    MAKE NEW CONTROLLER\n
        \e[1mc\e[0m, \e[1mcontroller\e[0m [name] [options]\n\t
        OPTIONS\n\t
            \e[1m-db\e[0m, \e[1m--database\e[0m    With Database Methods\n\n\t
    MAKE NEW MODEL\n
        \e[1mm\e[0m, \e[1mmodel\e[0m [name] [options]\n\t
        OPTIONS\n\t
            \e[1m-db\e[0m, \e[1m--database\e[0m    With Database Methods\n\n\t
    MAKE NEW MODEL AND CONTROLLER\n
        \e[1mmc\e[0m [name] [options]\n\t
        OPTIONS\n\t
            \e[1m-db\e[0m, \e[1m--database\e[0m    With Database Methods\n\n\t
    MAKE NEW VIEW\n
        \e[1mv\e[0m, \e[1mview\e[0m [name]\n\n\t
    MAKE NEW TYPE\n
        \e[1mt\e[0m, \e[1mtype\e[0m [name]\n\n\t
    MAKE NEW PROVIDER\n
        \e[1mp\e[0m, \e[1mprovider\e[0m [name]\n\n
    UPDATE DB SCHEMA  -  !!! WARNING: Use at your own risk. Make sure to backup your database before you run this command, as it can be DESTRUCTIVE!\n
        \e[1mu\e[0m, \e[1mupdate\e[0m\n\n
        OPTIONS\n\t
            \e[1m-f\e[0m, \e[1m--force\e[0m    Force Destructive Calls\n\n\t
    EXAMPLE USAGES:\n
        php spry controller Users
        php spry model User
        php spry mc Users
        php spry view User/AddUser\n\n";
    }
}
