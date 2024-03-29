<?php
class Authors
{
    private $db;
    public function __construct()
    {
        $this->db = new Database;
    }

    public function getWikies()
    {
        $this->db->query('SELECT wikis.*, categories.category_name, GROUP_CONCAT(tags.name_tag) AS tags
        FROM wikis
        LEFT JOIN categories ON wikis.category_id = categories.category_id
        LEFT JOIN wiki_tag ON wikis.wiki_id = wiki_tag.wiki_id
        LEFT JOIN tags ON wiki_tag.tag_id = tags.id_tag
        WHERE wikis.isArchived = 0
        GROUP BY wikis.wiki_id');
        $row = $this->db->fetchAll();
        return $row;
    }

    public function getWiki($id)
    {
        $this->db->query('SELECT wikis.*, users.username, categories.category_name, GROUP_CONCAT(tags.name_tag) AS tags
        FROM wikis
        LEFT JOIN categories ON wikis.category_id = categories.category_id
        LEFT JOIN wiki_tag ON wikis.wiki_id = wiki_tag.wiki_id
        LEFT JOIN tags ON wiki_tag.tag_id = tags.id_tag
        LEFT JOIN users ON users.user_id = wikis.author_id
        WHERE wikis.wiki_id = :id
        GROUP BY wikis.wiki_id;
        ');
        $this->db->bind(':id', $id);
        $row = $this->db->fetch();
        return $row;
    }

    public function getCategories()
    {
        $this->db->query('SELECT * FROM categories');
        $row = $this->db->fetchAll();
        return $row;
    }

    public function getTags()
    {
        $this->db->query('SELECT * FROM tags');
        $tags = $this->db->fetchAll();
        return $tags;
    }

    public function addWiki($data)
    {
        try {
            $this->db->beginTransaction();

            $this->db->query('INSERT INTO wikis (title, content, author_id, category_id) VALUES (:title, :content, :author_id, :category_id)');
            $this->db->bind(':title', $data['title']);
            $this->db->bind(':content', $data['content']);
            $this->db->bind(':author_id', $data['author_id']);
            $this->db->bind(':category_id', $data['category_id']);
            $this->db->execute();

            $wikiId = $this->db->lastInsertId();

            foreach ($data['tag_id'] as $tag_id) {
                $this->db->query('INSERT INTO wiki_tag (wiki_id, tag_id) VALUES (:wiki_id, :tag_id)');
                $this->db->bind(':wiki_id', $wikiId);
                $this->db->bind(':tag_id', $tag_id);
                $this->db->execute();
            }

            $this->db->commit();

            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "Transaction failed: " . $e->getMessage();
            return false;
        }
    }


    public function lastInsertId()
    {
        $lastInsertId = $this->db->lastInsertId();
        return $lastInsertId;
    }

    public function updateWiki($data)
    {
        try {
            $this->db->beginTransaction();

            // Update the main wiki information
            $this->db->query('UPDATE wikis SET title = :title, content = :content, category_id = :category_id WHERE wiki_id = :wiki_id');
            $this->db->bind(':wiki_id', $data['wiki_id']);
            $this->db->bind(':title', $data['title']);
            $this->db->bind(':content', $data['content']);
            $this->db->bind(':category_id', $data['category_id']);
            $this->db->execute();

            $this->db->query('DELETE FROM wiki_tag WHERE wiki_id = :wiki_id');
            $this->db->bind(':wiki_id', $data['wiki_id']);
            $this->db->execute();

            foreach ($data['tag_id'] as $tag_id) {
                $this->db->query('INSERT INTO wiki_tag (wiki_id, tag_id) VALUES (:wiki_id, :tag_id)');
                $this->db->bind(':wiki_id', $data['wiki_id']);
                $this->db->bind(':tag_id', $tag_id);
                $this->db->execute();
            }

            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "Transaction failed: " . $e->getMessage();
            return false;
        }
    }

    public function getSelectedTags($id)
    {
        $this->db->query("SELECT * FROM tags join wiki_tag on wiki_tag.tag_id = tags.id_tag WHERE wiki_tag.wiki_id = :wiki_id ");
        $this->db->bind(':wiki_id', $id);
        $row = $this->db->fetchAll();
        return $row;
    }


    public function deleteWiki($id)
    {
        try {
            $this->db->beginTransaction();

            // Delete from the wiki_tag table
            $this->db->query('DELETE FROM wiki_tag WHERE wiki_id = :wiki_id');
            $this->db->bind(':wiki_id', $id);
            $this->db->execute();

            // Delete from the wikis table
            $this->db->query('DELETE FROM wikis WHERE wiki_id = :wiki_id');
            $this->db->bind(':wiki_id', $id);
            $this->db->execute();

            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            echo "Transaction failed: " . $e->getMessage();
            return false;
        }
    }


}
