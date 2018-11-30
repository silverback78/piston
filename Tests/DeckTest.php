<?php
require_once('Models/User.php');
require_once('Models/Deck.php');
require_once('Models/Cards.php');
require_once('Services/Utils.php');
require_once('Services/DB.php');
require_once('Tests/Mocks/ReCaptcha.php');

use PHPUnit\Framework\TestCase;

final class DeckTest extends TestCase
{
    public $testDeckName = 'deck';
    public $testNewDeckName = 'newdeck';
    public $testWrongDeckName = 'wrongdeck';
    public $testDeckDescription = 'This is the deck description.';
    public $testDeckNewDescription = 'This is the new deck description.';
    public $testUsername = 'username';
    public $testPassword = 'password';
    public $testBadPassword = 'notpassword';

    public function DeleteUser() {
        DB::executeSql("DELETE FROM users WHERE username = :username", array(
            ':username' => $this->testUsername
        ));
    }

    public function CreateUser() {
        $user = new User();
        $data = [];
        $data['username'] = $this->testUsername;
        $data['password'] = $this->testPassword;
        $user->Create($data);
    }

    public function CreateDeck() {
        $this->CreateUser();
        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data = [];
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
    }

    public function setUp() {
        $this->DeleteUser();
    }

    public function tearDown() {
        $this->DeleteUser();
    }

    public function testInstantiateDeckWithNoParameters()
    {
        $this->DeleteUser();

        $deck = new Deck();
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testInstantiateDeckWithNoUser()
    {
        $this->DeleteUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Unable to load user, username not found.');
        $this->assertEquals($deck->referenceCode, 105);
    }

    public function testInstantiateDeckWithBadAuthentication()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testInstantiateDeckWithAuthentication()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testCreateDeckNotAuthenticated()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);

        $data['description'] = $this->testDeckDescription;

        $deck->Create($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testCreateDeck()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testCreateDeckNoUser()
    {
        $this->CreateUser();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testCreateDuplicateDeck()
    {
        $this->CreateUser();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckDescription;
        $deck->Create($data);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Duplicate deck name found.');
        $this->assertEquals($deck->referenceCode, 202);
    }

    public function testReadDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testReadDeckAuthenticated()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testReadDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testUpdateDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckNewDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testUpdateNewDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testNewDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testNewDeckName);
        $this->assertEquals($deck->description, $this->testDeckNewDescription);
        $this->assertEquals($deck->statusCode, 200);

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Read();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testUpdateDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testUpdateDeckNoPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->id, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testUpdateDeckNoUser()
    {
        $this->CreateUser();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $data['description'] = $this->testDeckNewDescription;
        $deck->Update($data);
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testDeleteDeckBadPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testBadPassword);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed, no email on file.');
        $this->assertEquals($deck->referenceCode, 108);
    }

    public function testDeleteDeckNoPassword()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, false);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testDeleteDeck()
    {
        $this->CreateDeck();

        $deck = new Deck($this->testUsername, $this->testDeckName, $this->testPassword);
        $deck->Delete();
        $this->assertEquals($deck->user->authenticated, true);
        $this->assertEquals($deck->deckName, $this->testDeckName);
        $this->assertEquals($deck->description, $this->testDeckDescription);
        $this->assertEquals($deck->statusCode, 200);
    }

    public function testDeleteDeckNoUser()
    {
        $this->CreateDeck();

        $deck = new Deck(null, $this->testDeckName, $this->testPassword);
        $deck->Delete();
        $this->assertEquals($deck->user, null);
        $this->assertEquals($deck->deckName, null);
        $this->assertEquals($deck->description, null);
        $this->assertEquals($deck->statusCode, 400);
        $this->assertEquals($deck->message, 'Authentication failed.');
        $this->assertEquals($deck->referenceCode, 113);
    }

    public function testCreateManyDecksWithCards() {
        $decks = '[{"name":"Types of Thinking","description":"","cards":[{"term":"First-order Thinking","definition":"Ordinary thinking; Spontaneous and non-reflective; Contains insight, prejudice, good and bad reasoning; Indiscriminately combined"},{"term":"Second-order Thinking","definition":"Critical thinking; First-order thinking that is consciously realized"},{"term":"Weak-sense Thinking","definition":"Ignore flaws; Seek to win arguments; Lacks fair-mindedness"},{"term":"Strong-sense Thinking","definition":"Pursues what is fair and just; Strives to be ethical; Entertains arguments they do not agree with"},{"term":"Three functions of the mind","definition":"Thinking, feeling, wanting"},{"term":"Sociocentrism","definition":"The assumption that one\'s own social group is inherently superior to others"},{"term":"Sophistry","definition":"The ability to win an argument despite flows in its reasoning"}]},{"name":"Reasoning","description":"","cards":[{"term":"Point of View","definition":"The perspective from which something is observed or thought through"},{"term":"Clarity","definition":"Easily understood"},{"term":"Accuracy","definition":"Being near to the true value or meaning of something"},{"term":"Inference","definition":"Logical process of drawing conclusions"},{"term":"Implication","definition":"Logically follows from reasoning"},{"term":"Purpose","definition":"The goal of reasoning"},{"term":"Concepts","definition":"General categories by which we interpret or classify information used in our thinking"},{"term":"Assumptions","definition":"Unstated or hidden beliefs that support our explicit reasoning"},{"term":"Precision","definition":"Being precise or exact"}]},{"name":"Systematic Problem Solving","description":"","cards":[{"term":"Socratic Method","definition":"Systematic, disciplined approach to asking questions aimed at truth"},{"term":"Common Factor","definition":"In analyzing causation, Looking for a single shared factor"},{"term":"Single Difference","definition":"In analyzing causation, looking for a causal factor that is present in one situation but absent in another, similar situation"},{"term":"Concomitant Variation","definition":"In analyzing causation, looking for a pattern of variation between a possible cause and a possible effect"},{"term":"Process of Elimination","definition":"In analyzing causation, successively ruling out non-causal factors until one correct causal factor remains"}]},{"name":"Assumptions, Biases, and Common Fallacies","description":"","cards":[{"term":"Inert Information","definition":"Memorized information that is not understood and can\'t be used critically"},{"term":"Activated Ignorance","definition":"Taking into the mind and actively using information that is false although we mistakenly think it is true"},{"term":"Activated Knowledge","definition":"Taking into the mind and actively using information that is true and also, when understood insightfully, leads us by implication to more and more knowledge"},{"term":"Uncritical Persons","definition":"Intellectually unskilled thinkers"},{"term":"Skilled Manipulators","definition":"Weak-sense critical thinkers"},{"term":"Fair-Minded Critical Persons","definition":"Strong-sense critical thinkers"}]},{"name":"Fallacies","description":"","cards":[{"term":"Ad Hominem","definition":"Dismissing an argument by attacking the person who offers it rather than by refuting its reasoning"},{"term":"Appeal to Authority","definition":"To justify support for a position by citing an esteemed or well-known figure who supports it"},{"term":"Appeal to Experience","definition":"Claiming to speak with the \"Voice of Experience\" in support of an argument, even when it may not be relevant"},{"term":"Appeal to Fear","definition":"Citing threat or possibility of a frightening outcome as the reason for supporting an argument"},{"term":"Appeal to Popularity","definition":"Citing majority sentiment or popular opinion as the reason for supporting a claim."},{"term":"Attacking Evidence","definition":"Focuses on discrediting the underlying evidence for an argument, questioning its validity"},{"term":"Begging the Question","definition":"Asserting a conclusion that is assumed in the reasoning. The reason given to support the conclusion restates the conclusion"},{"term":"Denying Inconsistencies","definition":"Refusing to admit contradictions or inconsistencies when making an argument or defending a position"},{"term":"Either-or","definition":"Assuming only two alternatives win, when in reality there are more than two"},{"term":"Evading Questions","definition":"Avoiding direct and truthful answers to difficult questions through diversionary tactics, vagueness or deliberately confusing or complex responses"},{"term":"Faulty Analogy","definition":"Drawing an invalid comparison between things for the purpose of either supporting or refuting some position"},{"term":"Hard-cruel-world Argument","definition":"Justifying illegal or unethical practices by arguing they are necessary to confront a greater evil or threat"},{"term":"Hasty Generalization","definition":"Inferring a general proposition about something based on too small a sample or unrepresentative sample"},{"term":"Red Herring","definition":"Introducing an irrelevant point or topic to divert attention from the issue at hand"},{"term":"Search for Perfect Solution","definition":"Asserting that a solution is not worth adopting because it does not fix the problem completely"},{"term":"Straw Man","definition":"The use of irrelevant, misleading, or questionable statistics to support an argument or defend a position"},{"term":"Two Wrongs Make a Right","definition":"Defending or justifying our wrong position or conduct by pointing to a similar wrong done by someone else"},{"term":"Treating Abstracts as Reality","definition":"Citing abstract concepts (freedom, justice, science) to support an argument or to call for action"}]},{"name":"Evidence","description":"","cards":[{"term":"Qualitative Evidence","definition":"Evidence that describes an observation or phenomenon and communicates its meaning"},{"term":"Quantitave Evidence","definition":"Evidence that quantifies an observation or phenomenon and is concerned with determining causation"},{"term":"Factual Claims","definition":"Beliefs about the way the world is, was, or will be whose credibility depends on the quality of evidence offered to support them"},{"term":"Analogy","definition":"Inference that if 2 things are alike in one respect, they will be alike in other respects"},{"term":"Rival Cause","definition":"A plausible alternative explanation for why a certain outcome happened"},{"term":"Empirical","definition":"Based on or derived from practical experiment and direct observation"}]}]';
        $decks = json_decode($decks);

        $this->CreateUser();

        foreach ($decks as $deck) {
            $data = [];
            $data['description'] = $deck->description;
            $data['category'] = 'category';
            $cardsData = [];

            $newDeck = new Deck($this->testUsername, $deck->name, $this->testPassword);
            $newDeck->Create($data);

            DB::executeQuery('deck',"SELECT name, category FROM decks WHERE id = :deckId", array(
                ':deckId' => $newDeck->id
            ));

            $this->assertEquals(DB::$results['deck'][0]['name'], Utils::UrlSafe($deck->name));

            foreach ($deck ->cards as $card) {
                $thisCard = [];
                $thisCard['term'] = $card->term;
                $thisCard['definition'] = $card->definition;
                $cardsData[] = $thisCard;
            }

            $data['cards'] = $cardsData;

            $newCards = new Cards($this->testUsername, $deck->name, $this->testPassword);
            $newCards->Update($data);

            foreach ($cardsData as $card) {
                DB::executeQuery('cards',"SELECT term, definition FROM cards WHERE deck_id = :deckId and term = :term", array(
                    ':deckId' => $newDeck->id,
                    ':term' => $card['term']
                ));

                $this->assertEquals(DB::$results['cards'][0]['term'], $card['term']);
                $this->assertEquals(DB::$results['cards'][0]['definition'], $card['definition']);
            }
        }
    }
}


