<?php
/**
 * Classe Connexion.
 *
 * Cette classe permet de gérer la connexion à la base de données et d'exécuter des requêtes SQL.
 */
include_once(__DIR__ . "/database.php");

class Connexion
{
    /** @var PDO $pdo Instance de PDO pour la connexion à la base de données. */
    private $pdo;

    /** @var Connexion $_instance Instance unique de la classe Connexion. */
    private static $_instance;

    /**
     * Constructeur privé de la classe Connexion.
     * Initialise la connexion à la base de données.
     */
    private function __construct()
    {
        try {
            $this->pdo = new PDO(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
        } catch (PDOException $e) {
            /**
             * Gère une exception PDOException en affichant un message d'erreur.
             */
            print "Erreur !: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    /**
     * Obtient une instance unique de la classe Connexion.
     *
     * @return Connexion Instance unique de la classe Connexion.
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Connexion();
        }
        return self::$_instance;
    }

    /**
     * Exécute une requête SELECT dans la base de données MySQL.
     *
     * @param string $query Requête SQL à exécuter.
     * @param array $params Paramètres à lier à la requête (optionnel).
     * @return array Résultat de la requête SELECT.
     */
    public function selectQuery($query, $params = [])
    {
        try {
            $queryRes = $this->pdo->prepare($query);
            $queryRes->execute($params);
            return $queryRes->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            /**
             * Gère une exception PDOException en affichant un message d'erreur.
             */
            print "Erreur !: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    /**
     * Exécute une requête SQL dans la base de données MySQL.
     *
     * @param string $query Requête SQL à exécuter.
     * @param array $params Paramètres à lier à la requête (optionnel).
     * @return PDOStatement Résultat de la requête.
     */
    public function ExecuteQuery($query, $params = [])
    {
        try {
            if (!empty($params)) {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
            } else {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute();
            }
            return $stmt;
        } catch (PDOException $e) {
            /**
             * Gère une exception PDOException en affichant un message d'erreur.
             */
            print "Erreur !: " . $e->getMessage() . "<br/>";
            die();
        }
    }
    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}
?>