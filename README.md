# <center>--------------API ВОДАХРАНЕНИИ---------</center>

<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Api</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
  </ol>
</details>

<!-- ABOUT THE PROJECT -->

## About The Project

Design and develop a simple crud api for journals from a database offered by the client.

### Built With

- [PHP](https://www.php.net/)
- [Microsoft Sql Server Management Studio](https://www.microsoft.com/ru-ru/sql-server/sql-server-2019)

<!-- GETTING STARTED -->

## Getting Started

Instructions for setting up your project locally.
To get a local copy up and running follow these simple example steps.

### Installation

1. Clone the repo.
   ```bash
   https://github.com/EsneiderCode/vada.git
   ```
2. Install XAMPP.
   ```bash
   https://www.apachefriends.org/es/index.html
   ```
3. Create folder config and a file into the folder calls "dbconfig.php".

4. Put the db config into the file.
5. Launch Xampp
   ```bash
   Connect Apache and Mysql.
   ```

### Server

1. Create local server .
   ```bash
   php -S localhost:8080
   ```

<!-- USAGE EXAMPLES -->

## REQUESTS:

GET:
1- GET ALL JOURNALS => Just do request to get, don't indicate nothing else.

GET:
2- GET INFORMATION BY JOURNAL WITH MINIMUN AND MAXIMUN DATE => Indicate as query ALL next params : "id_journal", "from" , "to" .

GET:
3- GET JOURNALS STRUCTURE => Indicate as query next params: "struct" , "id_journal".

GET:
4- GET JOURNALS BVU => Indicate as query param : "journals_list_bvu" as true.

GET:
4- GET JOURNALS BY JOURNAL BVU => Indicate as query param: "id_journal_bvu" and id journal number.

POST:
1- CREATE NEW REGISTER IN ONE JOURNAL => Indicate as query : "id_journal" AND as body json with ALL journal fields (columns) to create.

PUT:
1- UPDATE JOURNAL REGISTER => Indicate as query next params: "id_journal" , "id_reg" AND as body json with ALL journal fields (columns) to update.

DELETE:
1- DELETE JOURNAL REGISTER => indicate as body next params: "id_journal" , "id_reg".

DELETE:
2- DELETE JOURNAL => Indicate as body next param: "id_journal"
