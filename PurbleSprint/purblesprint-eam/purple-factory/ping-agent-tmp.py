import asyncio, logging, os
from dotenv import load_dotenv
from google.antigravity import Agent, LocalAgentConfig, CapabilitiesConfig
load_dotenv('.env')
logging.disable(logging.CRITICAL)
async def main():
    try:
        config=LocalAgentConfig(model=os.getenv('ANTIGRAVITY_MODEL'),system_instructions='Responda somente PRONTO.',capabilities=CapabilitiesConfig(enabled_tools=[]))
        async with Agent(config) as agent:
            response=await asyncio.wait_for(agent.chat('Responda somente PRONTO.'),timeout=60)
            print('Antigravity API: resposta recebida' if (await response.text()).strip() else 'Antigravity API: resposta vazia')
    except Exception as erro:
        print('Antigravity API:',type(erro).__name__)
asyncio.run(main())
