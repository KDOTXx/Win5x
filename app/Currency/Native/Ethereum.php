<?php namespace App\Currency\Native;

use App\Currency\Currency;
use App\Currency\CurrencyTransactionResult;
use App\Currency\Option\WalletOption;
use App\Models\Settings;
use App\Models\User;
use Bezhanov\Ethereum\Converter;
use Illuminate\Support\Facades\Log;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;
use kornrunner\Ethereum\Address;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumTx\EIP1559Transaction;
use Symfony\Component\Process\Process;



class Ethereum extends Currency {

  function id(): string {
    return "infura_eth";
  }

  public function walletId(): string {
    return "eth";
  }

  function name(): string {
    return "ETH";
  }

  public function alias(): string {
    return "ethereum";
  }

  public function displayName(): string {
    return "Ethereum";
  }

  function icon(): string {
    return "eth";
  }

  public function style(): string {
    return "#627eea";
  }

  public function isRunning(): bool {
    return true;
  }

  public function newWalletAddress(?User $user, ?string $chainId = null): string {
    $address = new Address();

    file_put_contents(storage_path('app/ethereumPrivateKeys/0x' . $address->get() . '.json'), json_encode([
      'address' => '0x' . $address->get(),
      'privateKey' => $address->getPrivateKey(),
      'publicKey' => $address->getPublicKey()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return '0x' . $address->get();
  }

  private function balance($account) {
    return number_format(floatval(Settings::get('ethereumBalance', '0')), 8, '.', '');
  }

  public function setupWallet() {}

  public function send(string $from, string $to, float $sum) {

    $compile = function(string $from, string $to, float $sum): array { // $from parameter is a private key, $to parameter is recipient address
      if(\App\Utils\Demo::isDemo()) return [
        'error' => [
          'This action is not available in the demo version.'
        ],
        'code' => 1
      ];

      $error = [];

      //$process = new Process(['npm', 'run', 'build']);
      $process = new Process(['node', 'send.js', $from, $to, $sum]);
      $process->setTimeout(null);
      $process->setWorkingDirectory(base_path());

      $code = $process->run(function ($type, $line) use (&$error) {
        if ($type == 'err') $error[] = $line;
      });

      return [
        'error' => $error,
        'code' => $code
      ];
    };

    $addressData = json_decode(file_get_contents(base_path('storage/app/ethereumPrivateKeys/'  . $from . '.json')));
    $result = $compile($addressData->privateKey, $to, $sum);
    if($result['code'] !== 0) Log::error($result);

    // try {
    //   $password = User::where('wallet_infura_eth', $from)->first()->_id;

    //   $this->getClient()->getPersonal()->unlockAccount($from, $password, function ($err, $unlocked) use ($to, $sum, $from) {
    //     if ($err != null) {
    //       Log::critical($err);
    //       return;
    //     }

    //     $gas = 21000;
    //     $gasPriceGwei = 20;

    //     $this->getClient()->getEth()->getBalance($from, function ($err, $balance) use ($sum, $gas, $gasPriceGwei, $to, $from) {
    //       $ethBalance = floatval((new Converter())->fromWei($balance, 'ether'));

    //       $txValue = $ethBalance - ($gasPriceGwei / 1000000000) * $gas;

    //       if ($gas * ($gasPriceGwei / 1000000000) > $ethBalance) {
    //         Log::error("Insufficient funds for gas*price+value");
    //         return;
    //       }

    //       $this->getClient()->getEth()->sendTransaction([
    //         'to' => $to,
    //         'from' => $from,
    //         'value' => '0x' . dechex(intval((new Converter())->toWei($txValue, 'ether'))),
    //         'gas' => '0x' . dechex($gas),
    //         'gasPrice' => '0x' . dechex($gasPriceGwei * 1000000000)
    //       ], function ($err) {
    //         if ($err !== null) Log::critical($err);
    //       });
    //     });
    //   });
    // } catch (\Exception $e) {

    // }
      // try {
      //   $this->getClient()->getEth()->getTransactionCount($from, 'pending', function ($err, $nonce) use ($to, $sum, $from) {
      //     if ($err !== null) {
      //         Log::info("Error: " . $err->getMessage());
      //     }

      //     Log::info($nonce);

      //     $gas = 21000;
      //     $gasPriceGwei = 20;

      //     // $transaction = new Transaction([
      //     //     'nonce' => sprintf(decimalHex(($nonce->toString()))),
      //     //     "gasPrice" => decimalHex($this->gasPrice->toString()),
      //     //     "gasLimit" => decimalHex($this->gasEstimate->toString()),
      //     //     'from' => $from,
      //     //     'to' => $to,
      //     //     'value' => sprintf(decimalHex(etherWei(0.0345))),
      //     //     'chainId' => 1
      //     // ]);

      //    $this->getClient()->getEth()->getBalance($from, function ($err, $balance) use ($sum, $gas, $gasPriceGwei, $to, $from, $nonce) {
      //     $ethBalance = floatval((new Converter())->fromWei($balance, 'ether'));
      //     Log::info($from);
      //     Log::info($balance);
      //     Log::info($ethBalance);

      //     $txValue = $ethBalance - ($gasPriceGwei / 1000000000) * $gas;

      //     Log::info($txValue);

      //     // if ($gas * ($gasPriceGwei / 1000000000) > $ethBalance) {
      //     //   Log::error("Insufficient funds for gas*price+value");
      //     //   return;
      //     // }

      //     $transaction = new Transaction([
      //         'nonce' => dechex($nonce->toString()),
      //         'gas' => '0x' . dechex(21000),
      //         //'gasPrice' => '0x4cf6f2878',
      //         'gasPrice' => '0x' . dechex(25 / 1000000000),
      //       //  "gasLimit" => decimalHex($this->gasEstimate->toString()),
      //         'gasLimit' => '0x' . dechex(21000 * 50),
      //         'from' => $from,
      //         'to' => $to,
      //         'value' => '0x' . dechex(intval((new Converter())->toWei(0.002, 'ether'))),
      //         'chainId' => 1
      //     ]);

      //     Log::info(dechex($nonce->toString()));
      //     // $transaction = new EIP1559Transaction([
      //     //     'nonce' => dechex($nonce->toString()),
      //     //     'from' => $from,
      //     //     'to' => $to,
      //     //     'maxPriorityFeePerGas' => '0xfab071',
      //     //     'maxFeePerGas' => '0x' . dechex(55 / 1000000000),
      //     //     'gas' => '0x' . dechex(24000),
      //     //     'gasLimit' => '0x' . dechex(10000000),
      //     //     'gasPrice' => '0x4cf6f2878',
      //     //     'value' => '0x' . dechex(intval((new Converter())->toWei(0.001, 'ether'))),
      //     //     'chainId' => 1, // required
      //     //     'input' => '0x',
      //     //     'accessList' => [],
      //     //     'data' => ''
      //     // ]);

      //     // $this->getClient()->getEth()->sendTransaction([
      //     //   'to' => $to,
      //     //   'from' => $from,
      //     //   'value' => '0x' . dechex(intval((new Converter())->toWei($txValue, 'ether'))),
      //     //   'gas' => '0x' . dechex($gas),
      //     //   'gasPrice' => '0x' . dechex($gasPriceGwei * 1000000000)
      //     // ], function ($err) {
      //     //   if ($err !== null) Log::critical($err);
      //     // });

      //     $addressData = json_decode(file_get_contents(base_path('storage/app/ethereumPrivateKeys/'  . $from . '.json')));

      //    // Log::info($addressData);

      //     $signedTransaction = $transaction->sign($addressData->privateKey);

      //     $this->getClient()->getEth()->sendRawTransaction(sprintf('0x%s', $signedTransaction), function ($err, $tx) {
      //         if ($err !== null) {
      //             Log::info("Error: " . $err->getMessage());
      //         }
      //         Log::info('TX: ' . $tx);
      //     });

      //   });

      //     // $signedTransaction = $transaction->sign($privateKey);

      //     // $this->getClient()->getEth()->sendRawTransaction(sprintf('0x%s', $signedTransaction), function ($err, $tx) {
      //     //     if ($err !== null) {
      //     //         Log::info("Error: " . $err->getMessage());
      //     //     }
      //     //     Log::info('TX: ' . $tx);
      //     // });
      //   });
      // } catch (\Exception $e) {
      //   Log::info($e);
      // }

  }

  public function url(): ?string {
    return "https://etherscan.io/address/%s";
  }

  public function coldWalletBalance(): float {
    return $this->balance($this->option('transfer_address')) ?? -1;
  }

  protected function getClient() {
    return new Web3(new HttpProvider(new HttpRequestManager('https://mainnet.infura.io/v3/'.$this->option('infura_api_key'), 30)));
  }

  public function process(string $wallet = null): string {
    $hasDeposit = false;

    try {
      $this->getClient()->getEth()->getTransactionByHash($wallet, function ($err, $response) use (&$hasDeposit, $wallet) {
        if ($err != null) {
          Log::critical($err);
          return;
        }

        if ($response == null) {
          Log::error('Invalid native_eth transaction response (null) for ' . $wallet);
          return;
        }

        //Log::info(json_encode($response));

        //if(isset($response->blockNumber)) $confirmations = intval($number->toString()) - hexdec($response->blockNumber);
        if (isset($response->to) && isset($response->blockNumber)) {
          $confirmations = intval(Currency::find($this->id())->option('confirmations'));

          $sum = hexdec($response->value) / pow(10, 18);
          if ($this->accept($confirmations, $response->to, $wallet, $sum)) {
            $ethBalance = floatval(Settings::get('ethereumBalance', '0'));
            Settings::set('ethereumBalance', $ethBalance + $sum);

            $hasDeposit = true;
          }
        }
      });
    } catch (\Exception $e) {
      Log::error('eth deposit verification error: ' . $e->getMessage());
      return CurrencyTransactionResult::$invalidTransaction;
    }

    return $hasDeposit ? CurrencyTransactionResult::$success : CurrencyTransactionResult::$invalidRecipientAddress;
  }

  protected function options(): array {
    return [
      new class extends WalletOption {
        function id() {
          return "infura_api_key";
        }

        function name(): string {
          return "Infura API key";
        }

        public function description(): string {
          return "https://infura.io/";
        }
      },
      new class extends WalletOption {
        public function id() {
          return "transfer_address";
        }

        public function name(): string {
          return "Transfer deposits to this address";
        }

        public function description(): string {
          return "";
        }

        public function readOnly(): bool {
          return true;
        }
      }
    ];
  }

}
