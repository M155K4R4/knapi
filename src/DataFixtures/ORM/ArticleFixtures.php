<?php

declare(strict_types=1);

namespace App\DataFixtures\ORM;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\CommentReply;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    private const ARTICLE_FIXTURES = __DIR__.'/../Resources/articles';

    private $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->connection = $entityManager->getConnection();
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function load(ObjectManager $manager): void
    {
        $this->createTriggers();

        $categoryCollection = CategoryFixtures::PUBLIC_CATEGORY_CODES;
        $usersCollection = UserFixtures::PUBLIC_USERNAMES;
        $tagCollection = TagFixtures::PUBLIC_TAG_CODES;
        $tagCollectionTotalItems = \count($tagCollection);

        /** @var \App\Entity\Image $image */
        $image = $this->getReference('image-card-photo-4.jpg');

        for ($i = 1; $i <= 10; ++$i) {
            \shuffle($usersCollection);
            /** @var \App\Entity\User $commentAuthor */
            $commentAuthor = $this->getReference(\sprintf('user-%s', $usersCollection[0]));

            \shuffle($categoryCollection);
            /** @var \App\Entity\Category $category */
            $category = $this->getReference(\sprintf('category-%s', $categoryCollection[0]));

            $articleTitle = \sprintf('Article %d', $i);

            $article = new Article();
            $article->setTitle($articleTitle);
            $article->setContent(\sprintf('Awesome short %s about.', $articleTitle));
            $article->setDescription(\sprintf('Awesome %s content.', $articleTitle));
            $article->setAuthor($commentAuthor);
            $article->setCategory($category);
            $article->setImage($image);

            // Add random number of random tags
            \shuffle($tagCollection);
            $tagsPerArticle = \random_int(1, $tagCollectionTotalItems);
            foreach ($tagCollection as $randomTag) {
                /** @var \App\Entity\Tag $tagReference */
                $tagReference = $this->getReference(\sprintf('tag-%s', $randomTag));
                $article->addTag($tagReference);
                if (0 === --$tagsPerArticle) {
                    break;
                }
            }

            $manager->persist($article);
        }

        // Predefined articles
        foreach ($this->getArticlesData() as $data) {
            $article = new Article();
            if (isset($data['image'])) {
                /** @var \App\Entity\Image $image */
                $image = $this->getReference($data['image']);
                $article->setImage($image);
            }
            $article->setTitle($data['title']);

            /** @var \App\Entity\User $author */
            $author = $this->getReference($data['author']);
            $article->setAuthor($author);

            /** @var \App\Entity\Category $category */
            $category = $this->getReference($data['category']);
            $article->setCategory($category);
            $article->setContent($data['content']);
            $article->setDescription($data['description']);

            foreach ($data['tags'] as $tagReference) {
                /** @var \App\Entity\Tag $tag */
                $tag = $this->getReference($tagReference);
                $article->addTag($tag);
            }

            if (isset($data['comments'])) {
                foreach ($data['comments'] as $commentData) {
                    $comment = new Comment();
                    /** @var \App\Entity\User $commentAuthor */
                    $commentAuthor = $this->getReference($commentData['author']);
                    $comment->setAuthor($commentAuthor);
                    $comment->setText($commentData['text']);
                    $comment->setArticle($article);

                    if (isset($commentData['replies'])) {
                        foreach ($commentData['replies'] as $replyData) {
                            $reply = new CommentReply();
                            /** @var \App\Entity\User $replyAuthor */
                            $replyAuthor = $this->getReference($replyData['author']);
                            $reply->setText($replyData['text']);
                            $reply->setAuthor($replyAuthor);
                            $reply->setComment($comment);

                            $manager->persist($reply);
                        }
                    }

                    $manager->persist($comment);
                }
            }

            $manager->persist($article);
        }

        $manager->flush();
    }

    /**
     * @throws \RuntimeException
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     *
     * @return Generator|array[]
     */
    public function getArticlesData(): Generator
    {
        $finder = Finder::create()
            ->in(self::ARTICLE_FIXTURES)
            ->name('*.yaml');

        foreach ($finder->getIterator() as $yamlFile) {
            yield Yaml::parse($yamlFile->getContents());
        }
    }

    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTriggers()
    {
        $driverName = $this->connection->getDriver()->getName();
        $sqlName = 'create_comment_triggers';
        $sqlFile = \sprintf('%s/../Resources/sql/%s/%s.sql', __DIR__, $driverName, $sqlName);

        if (!\is_file($sqlFile)) {
            dump(\sprintf('Notice: SQL File %s could not be found for driver %s', $sqlName, $driverName));

            return;
        }

        $this->connection->exec(\file_get_contents($sqlFile));
    }

    public function getDependencies(): array
    {
        return [
            TagFixtures::class,
            CategoryFixtures::class,
            UserFixtures::class,
            ImageFixtures::class,
        ];
    }
}
