<?php
declare(strict_types=1);

namespace App\Services;

use App\Catalog\DocumentCatalog;
use App\Repositories\DocumentoRepository;
use App\Repositories\ProjetoRepository;
use RuntimeException;
use ZipArchive;

/** Monta o ZIP da prestação de contas (specs/zip-prestacao.md, RF-21). */
final class ZipService
{
    public function __construct(
        private DocumentoRepository $documentos,
        private ProjetoRepository $projetos,
        private FileStorage $storage,
    ) {
    }

    /**
     * Cria o ZIP num arquivo temporário com os PDFs existentes, na ordem e com os nomes do catálogo (DEC-06).
     * Sem nenhum PDF, não cria arquivo (caminho null). Quem chama é responsável por apagar o temporário.
     *
     * @return array{caminho:?string,nome:string,incluidos:list<string>,ausentes:list<string>}
     * @throws RuntimeException quando o ZIP não pode ser criado (EX-Z03)
     */
    public function montar(int $projetoId): array
    {
        $projeto = $this->projetos->find($projetoId) ?? throw new RuntimeException('Projeto não encontrado.');
        $registros = $this->documentos->byProjeto($projetoId);

        $arquivos = [];
        $ausentes = [];
        foreach (DocumentCatalog::all() as $doc) {
            $reg = $registros[$doc['codigo']] ?? null;
            if ($reg === null) {
                continue;
            }
            if (!$this->storage->exists($reg['caminho'])) {
                $ausentes[] = $doc['codigo'];        // RN-Z03 / EX-Z02
                continue;
            }
            $arquivos[$doc['codigo']] = [$this->storage->absolute($reg['caminho']), $doc['arquivo']];
        }

        $resultado = ['caminho' => null, 'nome' => self::nomeArquivo($projeto), 'incluidos' => array_map('strval', array_keys($arquivos)), 'ausentes' => $ausentes];
        if ($arquivos === []) {
            return $resultado;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'prestacao-zip-');
        if ($tmp === false) {
            throw new RuntimeException('Não foi possível criar o arquivo temporário do ZIP.');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Não foi possível criar o ZIP.');
        }
        foreach ($arquivos as [$absoluto, $nome]) {
            // PDF já é comprimido: guarda sem recompressão (mais rápido, conteúdo idêntico)
            if (!$zip->addFile($absoluto, $nome) || !$zip->setCompressionName($nome, ZipArchive::CM_STORE)) {
                $zip->close();
                @unlink($tmp);
                throw new RuntimeException('Não foi possível incluir ' . $nome . ' no ZIP.');
            }
        }
        if (!$zip->close()) {
            @unlink($tmp);
            throw new RuntimeException('Não foi possível concluir o ZIP.');
        }
        $resultado['caminho'] = $tmp;
        return $resultado;
    }

    /** prestacao-de-contas-<município>-projeto-<N>.zip, sem acentos nem espaços (RF-Z03, DEC-Z1). */
    public static function nomeArquivo(array $projeto): string
    {
        $municipio = self::slug((string) ($projeto['municipio_nome'] ?? ''));
        return 'prestacao-de-contas-' . ($municipio !== '' ? $municipio . '-' : '') . 'projeto-' . (int) $projeto['id'] . '.zip';
    }

    public static function slug(string $texto): string
    {
        $mapa = ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n'];
        $t = strtr(mb_strtolower($texto), $mapa);
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    }
}
