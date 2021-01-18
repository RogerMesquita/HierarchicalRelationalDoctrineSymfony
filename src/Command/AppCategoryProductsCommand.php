<?php declare(strict_types=1);

namespace App\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\Category;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class AppCategoryProductsCommand
 * @package App\Command
 */
class AppCategoryProductsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'app:category:products';

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('List products in categories.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->entityManager->getRepository(Category::class);

        $qb = $repository->childrenQueryBuilder(null, false, null, 'ASC', false)
            ->join('App:Category', 'parent', Join::WITH, 'parent.lft <= node.lft AND parent.rgt >= node.lft')
            ->leftJoin('App:Product', 'item', Join::WITH, 'node = item.category')
            ->select('parent.id, parent.name, parent.lft, parent.rgt, parent.lvl, count(item.id) as nm')
            ->groupBy('parent.id')
            ->orderBy('parent.lft')
        ;

        $rows = $qb->getQuery()->getArrayResult();

        $tree = $repository->buildTree($rows, [
            'decorate' => true,
            'nodeDecorator' => function ($node)
            {
                return "$node[name] ($node[nm])";
            },
        ]);

        $io->writeln($tree);

        $io->success("Done");
    }

}