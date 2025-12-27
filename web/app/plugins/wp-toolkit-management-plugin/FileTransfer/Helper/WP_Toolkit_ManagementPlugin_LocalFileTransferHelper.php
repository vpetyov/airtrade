<?php
// Copyright 1999-2025. WebPros International GmbH. All rights reserved.

namespace PleskExt\WpToolkit\FileTransfer\Helper;

use PleskExt\WpToolkit\Application\Interfaces\PathComposerInterface;
use PleskExt\WpToolkit\Application\Interfaces\WptPathsInterface;
use PleskExt\WpToolkit\FileTransfer\ByteStream\LookaheadStringByteStream;
use PleskExt\WpToolkit\FileTransfer\FileTransferConstants;
use PleskExt\WpToolkit\FileTransfer\PacketHandlers\ErrorBypassPacketHandler;
use PleskExt\WpToolkit\FileTransfer\PacketHandlers\PacketTypeRouter;
use PleskExt\WpToolkit\FileTransfer\PacketRawDataStreamWriter\StderrPacketRawDataStreamWriter;
use PleskExt\WpToolkit\FileTransfer\Packets\ErrorPacket;
use PleskExt\WpToolkit\FileTransfer\ByteStreamPacketReader;
use PleskExt\WpToolkit\FileTransfer\PacketWriter\DirectoryStreamPacketWriter;
use PleskExt\WpToolkit\Helper\UnixShell;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

class LocalFileTransferHelper
{
    /**
     * @var string
     */
    private $wptPhpBin;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var WptPathsInterface
     */
    private $wptPaths;

    /**
     * @var PathComposerInterface
     */
    private $pathComposer;

    public function __construct(
        string $wptPhpBin,
        LoopInterface $loop,
        WptPathsInterface $wptPaths,
        PathComposerInterface $pathComposer
    ) {
        $this->wptPhpBin = $wptPhpBin;
        $this->loop = $loop;
        $this->wptPaths = $wptPaths;
        $this->pathComposer = $pathComposer;
    }

    public function run(
        string $sourceSystemUser,
        string $sourcePath,
        string $targetSystemUser,
        string $targetPath,
        bool $optionReplace,
        bool $optionDelete
    ): void {
        $plainStreamPacketWriter = new StderrPacketRawDataStreamWriter();
        $errorPacketWriter = new DirectoryStreamPacketWriter($plainStreamPacketWriter);

        $sourceStdout = '';
        $targetStdout = '';

        $readerCommandParts = [
            $this->wptPhpBin,
            $this->pathComposer->joinPath(
                $this->wptPaths->getScriptsDirectory(),
                'read-files.php'
            ),
            $sourceSystemUser,
            $sourcePath,
        ];

        if ($optionDelete) {
            $readerCommandParts[] = FileTransferConstants::OPTION_SEND_LIST_DIRECTORY_ITEMS;
        }

        $readerCommand = UnixShell::composeArgsCommand($readerCommandParts);
        $source = new Process($readerCommand);
        $source->start($this->loop);

        $writerCommandParts = [
            $this->wptPhpBin,
            $this->pathComposer->joinPath(
                $this->wptPaths->getScriptsDirectory(),
                'write-files.php'
            ),
            $targetSystemUser,
            $targetPath,
        ];

        if ($optionReplace) {
            $writerCommandParts[] = FileTransferConstants::OPTION_REPLACE_MODIFIED_ITEMS;
        }

        $writerCommand = UnixShell::composeArgsCommand($writerCommandParts);
        $target = new Process($writerCommand);
        $target->start($this->loop);

        $writerUnexpectedExit = false;
        $source->stderr->on('data', function ($chunk) use ($target, &$writerUnexpectedExit) {
            if (!$target->isRunning()) {
                $writerUnexpectedExit = true;
                return;
            }
            $target->stdin->write($chunk);
        });
        $source->stdout->on('data', function ($chunk) use (&$sourceStdout) {
            $sourceStdout .= $chunk;
        });
        $source->on('exit', function() use ($target) {
            $target->stdin->end();
        });

        $targetLookaheadStringStream = new LookaheadStringByteStream();
        $packetTypeRouter = new PacketTypeRouter();
        $targetPacketReader = new ByteStreamPacketReader($targetLookaheadStringStream);
        $targetPacketHandler = new ErrorBypassPacketHandler($errorPacketWriter);

        $target->stderr->on(
            'data',
            function ($chunk) use (
                $targetLookaheadStringStream, $packetTypeRouter, $targetPacketReader, $targetPacketHandler
            ) {
                $targetLookaheadStringStream->write($chunk);

                while (true) {
                    try {
                        $packet = $targetPacketReader->nextPacket();
                    } catch (\Exception $e) {
                        $targetLookaheadStringStream->rollback();
                        break;
                    }
                    $targetLookaheadStringStream->commit();

                    $packetTypeRouter->route($packet, $targetPacketHandler);
                    if (is_null($packet)) {
                        break;
                    }
                }
            }
        );
        $target->stdout->on('data', function ($chunk) use (&$targetStdout) {
            $targetStdout .= $chunk;
        });

        $this->loop->run();

        $sourceExitCode = $source->getExitCode();
        $targetExitCode = $target->getExitCode();

        $isReaderFailure = $sourceExitCode !== 0;
        /** @psalm-suppress TypeDoesNotContainType */
        $isWriterFailure = $targetExitCode !== 0 || $writerUnexpectedExit;


        if ($isReaderFailure) {
            $errorPacket = new ErrorPacket(
                ErrorPacket::ERROR_READER_FATAL_ERROR_CODE,
                [
                    ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_COMMAND => $readerCommand,
                    ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_EXIT_CODE => $sourceExitCode,
                    ErrorPacket::ERROR_READER_FATAL_ERROR_PARAM_OUTPUT => $sourceStdout,
                ]
            );
            $packetTypeRouter->route($errorPacket, $targetPacketHandler);
        }

        if (!$isReaderFailure && $sourceStdout !== '') {
            $errorPacket = new ErrorPacket(
                ErrorPacket::ERROR_READER_WARNINGS_CODE,
                [
                    ErrorPacket::ERROR_READER_WARNINGS_PARAM_COMMAND => $readerCommand,
                    ErrorPacket::ERROR_READER_WARNINGS_PARAM_OUTPUT => $sourceStdout,
                ]
            );
            $packetTypeRouter->route($errorPacket, $targetPacketHandler);
        }

        // It does not make sense to emit writer process failures if reader process has failed with error:
        // in most cases reader process will produce wrong data and writer process will fail with that data too.
        // So, in case of reader failure, fix it first before trying to check any writer process failures.
        if (!$isReaderFailure) {
            if ($isWriterFailure) {
                $errorPacket = new ErrorPacket(
                    ErrorPacket::ERROR_WRITER_FATAL_ERROR_CODE,
                    [
                        ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_COMMAND => $writerCommand,
                        ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_EXIT_CODE => $targetExitCode,
                        ErrorPacket::ERROR_WRITER_FATAL_ERROR_PARAM_OUTPUT => $targetStdout,
                    ]
                );
                $packetTypeRouter->route($errorPacket, $targetPacketHandler);
            }
        }

        if (!$isReaderFailure && !$isWriterFailure && $targetStdout !== '') {
            $errorPacket = new ErrorPacket(
                ErrorPacket::ERROR_WRITER_WARNINGS_CODE,
                [
                    ErrorPacket::ERROR_WRITER_WARNINGS_PARAM_COMMAND => $readerCommand,
                    ErrorPacket::ERROR_WRITER_WARNINGS_PARAM_OUTPUT => $targetStdout,
                ]
            );
            $packetTypeRouter->route($errorPacket, $targetPacketHandler);
        }
    }
}
