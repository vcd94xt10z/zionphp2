DELIMITER $$
-- SHOW VARIABLES LIKE '%creators%';
SET GLOBAL log_bin_trust_function_creators = 1;
CREATE FUNCTION `zion_nextval`(
	`seq_name` VARCHAR(100)
) RETURNS bigint(20)
BEGIN
	DECLARE returnval bigint;
    DECLARE countRow INT;
	    
    UPDATE `zion_sequence` 
	   SET `last_value` = `last_value` + 1
     WHERE `name` = seq_name;
		
	SET countRow = ROW_COUNT();
	IF(countRow = 0) THEN
		INSERT INTO `zion_sequence` 
        (`name`,`last_value`) 
		VALUES
		(seq_name,1);
    END IF;
 	
    SELECT `last_value` INTO returnval
      FROM `zion_sequence`
     WHERE `name` = seq_name
     LIMIT 1;
     
    RETURN returnval;
END$$
DELIMITER ;