<?php

namespace App\Controller;

use App\Service\BillingClient;
use App\Service\JwtTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(JwtTokenManager $jwtManager): Response
    {
        //$data = $jwtManager->isExpired('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MjExMTQ2OTcsImV4cCI6MTcyMTExODI5Nywicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoia3Jhc2lrb29vdkBnbWFpbC5jb20ifQ.gim8OYSDYAlQGTF7hwEDtjkTAnfdIovCY0VQLN0EMhgXUHwgpOWDdLfC_pH9EAB9rrPR15xMug_5NmtrrCtoriKsU37krevKvmM0KXI9yvWiaTC4gKoqmktrM-el73dxGcFhZCts5_RC5V14lA4qb-LCuCE8zXD3Po6Rbt_REvYv-mk-Ol8tWkG0qPrvTwWF3WRmPeM2wi9lM0VndV0LhsoTkrNctYlGwpcvvOMKosD0RAqieqQasI550b4QRKI5-fEp1jXsHtuT_aKItiy5ZRaao2RJWGXfySou5hFP0R1kpXCmnrF-p1uvvRz9MlHC5LvxE7PJJpOJgFtKtpg5Jq7BNuJuXtXjAM-yYP2YhTYJNxbbI4pz2pxYKwX3yb6XStEParAiwwsDW-GCV_5kkap3rU8Mpl8U6T657LFNPEh6i-SMQIZknC0Re9Kg5nQ6_udPicx7O-XcmJqDe4cfe3WpUS6xmgy-FJUKL61TtdIhoouMAHcDNF8Y6vRfsQ9i2yCSawoB7IrLqHbuUyPz4qboFmuA0qwcdQEcZ85FFaKOpkldIah1PhNyQpAgTb0jkEaDi19Y3QSYfzSjSUXSmwLB_bn4r4SfbO5xXVQOUWAtoSDY4waXLOUu24JiWEba8FnjiLpNYciG0xLSUeav51k-LPBL8zsgoNBpUEteSak');
        $data = $jwtManager->isExpired('eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MjExMTc3MjQsImV4cCI6MTcyMTEyMTMyNCwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoia3Jhc2lrb29vdkBnbWFpbC5jb20ifQ.gWijJbUEQhyZmMnvWg_NJ7FOayMULElF3RAmhjZWFCQP-Dq-MTy3co_FYUN_0z0l13XxABEmlTEXu7ZOPmzejZOoa6JQfFcMFMisbZfPp6FPQ5S9X47BBItj3U5MSSmuKZSuB4f3lTN65FKHSmpgGtJVh7o1WvMg9KJgP0O8IdzFp40jiRnA60arUnATny1Roxj69eJlCRdkG7Y2LMeYgcOglcFSbQP1CQ4WwwjgZBQMea-zNqHgRhS1NhImCpG6H7BViNQw-MFxRFnhV1cs0XeXjO9FEmUXpK1_Alxv_8RU347rzqWp4FpQHrrSbL_vclq4GzyKkVwIlhk3z66pzIbI79tj0_9I_661uLxs1t_J-A6h2GMAPuQgMsQ0C2kSNNZBI1T1GkbC5wo7_yKRewmcerSgCAXhwiS_hHu67X1P_7Zg9mz9xn-c7cYwAgy0j-bEnI8FNkEdfLlE7wwj9W9bK8yUvaEgQKmNQVdng3h-b2cU3PDg9V9HuWP8ctaS1nwSCVfOoLxtA1Tyfz-8rhZOh9usOWaA37T_ivDCg1DM3L8XHJ-XxELNLcpQ154zmU-eFGcPPFDczdB9QkF5nf1kCLF6eUzoBLkkhRzNn4GoV6wnUHwyndHhBsNoetrGWdX09dItwr0CYJHbbqAmNqdZ-YqUQgaughnYpgJVEGk');
        dd($data);
        return $this->render('test/index.html.twig', [
            'token' => '',
        ]);
    }
}
