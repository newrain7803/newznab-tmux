<?php

use newznab\db\Settings;


/**
 * This class handles storage and retrieval of release comments.
 */
class ReleaseComments
{

	/**
	 * @var \newznab\db\Settings
	 */
	public $pdo;

	/**
	 * @param \newznab\db\Settings $settings
	 */
	public function __construct($settings = null)
	{
		$this->pdo = ($settings instanceof Settings ? $settings : new Settings());
	}

	/**
	 * Get a comment by id.
	 */
	public function getCommentById($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM releasecomment WHERE id = %d", $id));
	}

	/**
	 * Get all comments for a GID.
	 */
	public function getCommentsByGid($gid)
	{
		return $this->pdo->query(sprintf("SELECT rc.id, text, createddate, sourceid, CASE WHEN sourceid = 0 THEN (SELECT username FROM users WHERE id = userid) ELSE username END AS username, CASE WHEN sourceid = 0 THEN (SELECT role FROM users WHERE id = userid) ELSE '-1' END AS role, CASE WHEN sourceid =0 THEN (SELECT r.name AS rolename FROM users AS u LEFT JOIN userroles AS r ON r.id = u.role WHERE u.id = userid) ELSE (SELECT description AS rolename FROM spotnabsources WHERE id = sourceid) END AS rolename FROM releasecomment rc WHERE isvisible = 1  AND gid = %s AND (userid IN (SELECT id FROM users) OR rc.username IS NOT NULL) ORDER BY createddate DESC LIMIT 100", $this->pdo->escapeString($gid)));
	}

	/**
	 * Get all comments for a release.GUID.
	 */
	public function getCommentsByGuid($guid)
	{
		return $this->pdo->query(sprintf("SELECT rc.id, text, createddate, sourceid, CASE WHEN sourceid = 0 THEN (SELECT username FROM users WHERE id = userid) ELSE username END AS username FROM releasecomment rc LEFT JOIN releases r ON r.gid = rc.gid WHERE isvisible = 1 AND guid = %s AND (userid IN (SELECT id FROM users) OR rc.username IS NOT NULL) ORDER BY createddate DESC LIMIT 100", $this->pdo->escapeString($guid)));
	}

	/**
	 * Get all count of all comments.
	 */
	public function getCommentCount($refdate=Null, $localOnly=Null)
	{
		if($refdate !== Null){
			if(is_string($refdate)){
			    // ensure we're in the right format
				$refdate=date("Y-m-d H:i:s", strtotime($refdate));
			}else if(is_int($refdate)){
			    // ensure we're in the right format
				$refdate=date("Y-m-d H:i:s", $refdate);
			}else{
				// leave it as null (bad content anyhow)
				$refdate = Null;
			}
		}

		$q = "SELECT count(id) AS num FROM releasecomment";
		$clause = [];
		if($refdate !== Null)
			$clause[] = "createddate >= '$refdate'";

        // set localOnly to Null to include both local and remote
        // set localOnly to true to only receive local comment count
        // set localOnly to false to only receive remote comment count
		if($localOnly === true){
			$clause[] = "sourceid = 0";
		}else if($localOnly === false){
			$clause[] = "sourceid != 0";
		}

		if(count($clause))
			$q .= " WHERE ".implode(" AND ", $clause);

		$res = $this->pdo->queryOneRow($q);
		return $res["num"];
	}

	/**
	 * Delete a comment.
	 */
	public function deleteComment($id)
	{
		$res = $this->getCommentById($id);
		if ($res)
		{
			$this->pdo->queryExec(sprintf("update releasecomment SET isvisible = 0 WHERE id = %d", $id));
			$this->updateReleaseCommentCount($res["gid"]);
		}
	}

	/**
	 * Delete all comments for a release.id.
	 */
	public function deleteCommentsForRelease($id)
	{
		$res = $this->getCommentById($id);
		if ($res)
		{
			$this->pdo->queryExec(sprintf("DELETE rc.* FROM releasecomment rc JOIN releases r ON r.gid = rc.gid WHERE r.id = %d", $id));
			$this->updateReleaseCommentCount($res["gid"]);
		}
	}

	/**
	 * Delete all comments for a users.id.
	 */
	public function deleteCommentsForUser($id)
	{
		$numcomments = $this->getCommentCountForUser($id);
		if ($numcomments > 0)
		{
			$comments = $this->getCommentsForUserRange($id, 0, $numcomments);
			foreach ($comments as $comment)
			{
				$this->deleteComment($comment["id"]);
				$this->updateReleaseCommentCount($comment["gid"]);
			}
		}
	}

	/**
	 * Add a releasecomment row.
	 */
	public function addComment($id, $gid, $text, $userid, $host)
	{
		if(strlen(trim($text)) == 0)
			return false;

		if ($this->pdo->getSetting('storeuserips') != "1")
			$host = "";

		$comid = $this->pdo->queryInsert(sprintf("INSERT INTO releasecomment (releaseid, gid, text, userid, createddate, host) VALUES (%d, %s, %s, %d, now(), %s)", $id, $this->pdo->escapeString($gid), $this->pdo->escapeString($text), $userid, $this->pdo->escapeString($host)));
		$this->updateReleaseCommentCount($gid);
		return $comid;
	}

	/**
	 * Get releasecomment rows by limit.
	 */
	public function getCommentsRange($start, $num)
	{
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;

		$sql = "SELECT rc.id, userid, guid, text, createddate, sourceid, CASE WHEN sourceID = 0 THEN (SELECT username FROM users WHERE id = userid) ELSE username END AS username, CASE WHEN sourceid = 0 THEN (SELECT role FROM users WHERE id = userid) ELSE '-1' END AS role, CASE WHEN sourceid =0 THEN (SELECT r.name AS rolename FROM users AS u LEFT JOIN userroles AS r ON r.id = u.role WHERE u.id = userid) ELSE (SELECT description AS rolename FROM spotnabsources WHERE id = sourceid) END AS rolename FROM releasecomment rc LEFT JOIN releases r ON r.gid = rc.gid WHERE isvisible = 1 AND (userid IN (SELECT id FROM users) OR rc.username IS NOT NULL) ORDER BY createddate DESC ".$limit;
		return $this->pdo->query($sql);
	}

	/**
	 * Update the denormalised count of comments for a release.
	 */
	public function updateReleaseCommentCount($gid)
	{
		$this->pdo->queryExec(sprintf("update releases
				SET comments = (SELECT count(id) FROM releasecomment WHERE releasecomment.gid = releases.gid AND isvisible = 1)
				WHERE releases.gid = %s", $this->pdo->escapeString($gid) ));
	}

	/**
	 * Get a count of all comments for a user.
	 */
	public function getCommentCountForUser($uid)
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT count(id) AS num FROM releasecomment WHERE userid = %d AND isvisible = 1", $uid));
		return $res["num"];
	}

	/**
	 * Get comments for a user by limit.
	 */
	public function getCommentsForUserRange($uid, $start, $num)
	{
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;

		return $this->pdo->query(sprintf("SELECT releasecomment.*, r.guid, r.searchname, users.username FROM releasecomment INNER JOIN releases r ON r.id = releasecomment.releaseid LEFT OUTER JOIN users ON users.id = releasecomment.userid WHERE userid = %d ORDER BY releasecomment.createddate DESC ".$limit, $uid));
	}
}
